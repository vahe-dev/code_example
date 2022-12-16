<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LogSystem;
use DB;
use Hash;
use Validator;
use Auth;
use Config;
use URL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;
use App\Models\{ Admin, Order, OrderQuote, OrderFiles, RevisionFiles, OrderNote, RevisionNote, OrderRevision, AdminOpen, User, Setting };
use Carbon\Carbon;
use App\Services\{ EncryptService, FileService, S3Service };
use App\Traits\{ OrderTraits, QuoteTraits, AdminTraits };

class AdminController extends Controller
{
    public function updateOrder(Request $request)
    {
        $orderID = $request->id;
        $order = Order::with(['orderFiles','settyFiles'])->where('id', $orderID)
            ->orderBy('id', 'DESC')
            ->first();

        if(empty($order)) {
            return redirect()
                ->route('admin.order.index')
                ->with('error', 'Invalid Order ID');
        }

        if($request->status != $order->status && $request->status == "forward_to_setty"){

            $settyEmails = ['test1@test.com', 'test2@test.com'];

            if (count($settyEmails)) {
                $data = array(
                    'job_number' => $order->job_number,
                );
                \Mail::send('emails.NewProjectAdded', $data, function ($message) use ($settyEmails) {
                    $message->from('test@test.com', "Your text");
                    $message->subject("New Project Added");
                    $message->to($settyEmails);
                });
            }
        }
        $admin = Auth::guard('admin')->user();

        if ( $request->has( 'setty_submit' ))
        {
            if ( $request->hasFile( 'file' ) ) {
                $files = $request->file( 'file' );
                $tmp_order_files = OrderFiles::where(array(
                    'order_id' => $orderID,
                    'user_type' => 1,
                ))->get()->each(function($tmp_file) {
                    $tmp_file->delete();
                });
                $s3_folder = $order->s3_folder;
                foreach ( $files as $file ) {
                    $result = null;

                    try {
                        $result = $this->s3Service->putFile('order', $s3_folder, $file);
                    } catch (Exception $e) {
                    }

                    if (!is_null($result)) {
                        $images_array = array(
                            'order_id'  => $orderID,
                            'file_name' => $result['file_name'],
                            'org_file_name' => $result['org_file_name'],
                            'user_id'   => $admin->id,
                            'user_type'  => 1
                        );
                        OrderFiles::create( $images_array );
                    }
                }
            }

            $order->setty_note = $request->setty_note;
            $order->setty_returned = 1;
            $order->updated_by = $admin->id;

            if($order->save())
            {
                return redirect()
                    ->route('admin.order.show', ['id'=>base64_encode($orderID)])
                    ->with('success', 'Order detail is successfully submitted!');
            }else{
                return redirect()
                    ->route('admin.order.show', ['id'=>base64_encode($orderID)])
                    ->with('error', 'Submission failed!');
            }
        }else if ($request->has( 'note_submit' )){
            $note = $request->note;
            if (empty($note))
                return redirect()
                    ->route('admin.order.show', ['id'=>base64_encode($orderID)])
                    ->with('error', 'Note should not be empty!');

            $admin_id = $admin->id;
            $order->updated_by = $admin_id;
            $order->save();
            $order_note = new OrderNote(array(
                'order_id'      => $orderID,
                'admin_id'      => $admin_id,
                'admin_type'    => 1,
                'note'          => $note,
            ));
            if($order_note->save())
            {
                if($order->status == 'forward_to_setty') {

                    $settyEmails = ['test1@test.com', 'test2@test.com'];

                    if (count($settyEmails)) {
                        $data = array(
                            'job_number' => $order->job_number,
                            'Comment' => $note,
                        );
                        \Mail::send('emails.NewCommentAdded', $data, function ($message) use ($settyEmails) {
                            $message->from('test@test.com', "Your text");
                            $message->subject("New Comment Added");
                            $message->to($settyEmails);
                        });
                    }
                }
                return redirect()
                    ->route('admin.order.show', ['id'=>base64_encode($orderID)])
                    ->with('success', 'Note is successfully submitted!');
            } else {
                return redirect()
                    ->route('admin.order.show', ['id'=>base64_encode($orderID)])
                    ->with('error', 'Submission failed!');
            }

        }else if ($request->has( 'send_payment_form')){
            $order->updated_by = $admin->id;
            $order->save();

            if($order->payment_status == 'pending')
            {
                $hashcode = EncryptService::encryptString($order->id);
                $data = array(
                    'payment_link' => route('payment.create', ['hashcode' => $hashcode, 'type' => 'order']),
                    'order_cancel_link' => route('frontend.order_cancel', ['hashcode' => $hashcode]),
                );
                \Mail::send( 'emails.Orderunpaid', $data, function( $message ) use ( $order ) {
                    $message->from( 'test@test.com', "Your text" );
                    $message->subject( "Payment is not completed." );
                    $message->to( $order->email );
                } );
                return redirect()
                    ->route('admin.order.show', ['id'=>base64_encode($order->id)])
                    ->with('success', 'Email has been sent!');
            }
        } else {
            return redirect()
                ->route('admin.order.show', ['id'=>base64_encode($orderID)])
                ->with('success', 'Order has been updated successfully');
        }
    }
}