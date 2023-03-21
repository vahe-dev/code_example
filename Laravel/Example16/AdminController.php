<?php
namespace App\Http\Controllers;

use App\Models\LogSystem;
use App\Models\Order;
use App\Models\OrderRevision;
use App\Models\RevisionFiles;
use App\Models\RevisionNote;
use App\Services\EncryptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdminController extends Controller
{
    /**
     * Update revision orders
     * */
    public function updateRevision(Request $request, $id)
    {
        $log = new LogSystem();
        $revisionID = base64_decode($id);
        $admin = Auth::guard('admin')->user();
        $revision = OrderRevision::where('id', $revisionID)
            ->first();

        $adminId = $admin->id;
        $log->logdata(__LINE__, __FILE__, __DIR__, __FUNCTION__, __CLASS__, __METHOD__, __NAMESPACE__,"Trying to update Revision by admin ID ". $adminId, null,'Revision'.var_export($revision, TRUE));

        if($request->status != 'cancelled'){
            $revision->is_cancelled = 0;
        }else{
            $revision->is_cancelled = 1;
        }

        if(empty($revision))
        {
            return redirect()
                ->route('admin.revision.index')
                ->with('error', 'Invalid Revision ID');
        }

        if($request->status != $revision->status && $request->status == "forward"){

            $settyEmails = ['test@test.test'];
            if (count($settyEmails)) {
                $data = array(
                    'job_number' => $revision->job_number,
                );
                \Mail::send('emails.NewProjectAdded', $data, function ($message) use ($settyEmails) {
                    $message->from('test@test.test', "test");
                    $message->subject("New Project Added");
                    $message->to($settyEmails);
                });
            }
        }

        if ( $request->has( 'submit' ))
        {
            if ( $request->hasFile( 'file' ) ) {

                $files = $request->file( 'file' );
                $s3_folder = $revision->s3_folder;

                foreach ( $files as $file ) {
                    $result = null;
                    try {
                        $result = $this->s3Service->putFile('revision', $s3_folder, $file);
                    } catch (Exception $e) {
                    }
                    if (!is_null($result)) {
                        $images_array = array(
                            'revision_id'  => $revisionID,
                            'file_name' => $result['file_name'],
                            'org_file_name' => $result['org_file_name'],
                            'user_id'   => $admin->id,
                            'user_type'  => 1
                        );
                        RevisionFiles::create( $images_array );
                    }
                }
            }
            $revision->setty_note = $request->setty_note;
            $revision->setty_returned = 1;
            $revision->updated_by = $admin->id;
            if($revision->save())
            {
                return redirect()
                    ->route('admin.revision.show', ['id'=>base64_encode($revisionID)])
                    ->with('success', 'Revision detail is successfully submitted!');
            }else{
                return redirect()
                    ->route('admin.revision.show', ['id'=>base64_encode($revisionID)])
                    ->with('error', 'Submission failed!');
            }
        }else if( $request->has( 'send_email' ) ){
            $revision->updated_by = $admin->id;
            $revision->save();
            $hashcode = EncryptService::encryptString($revisionID);
            $data = array(
                'revision_fee' => $this->getRevisionFee($revision),
                'payment_link' => route('payment.create', ['hashcode' => $hashcode, 'type' => 'revision']),
                'revision_cancel_link' => route('frontend.revision_cancel', ['hashcode' => $hashcode])
            );

            \Mail::send( 'emails.Revisionunpaid', $data, function( $message ) use ( $revision ) {
                $message->from( 'test@test.test', "test" );
                $message->subject( "Payment is not completed." );
                $message->to( $revision->email );
            });
            //send logs
            $log->logdata(__LINE__, __FILE__, __DIR__, __FUNCTION__, __CLASS__, __METHOD__, __NAMESPACE__,"Revision Email Sent to", null,'Revision Email '.var_export($revision->email, TRUE));
            return redirect()
                ->route('admin.revision.show', ['id' => base64_encode($revision->id)])
                ->with('success', 'Email sent successfully');
        }else if ($request->has( 'note_submit' )){
            $revision->updated_by = $admin->id;
            $revision->save();
            $note = $request->note;
            $admin_id = $admin->id;
            if(empty($note))
                return redirect()
                    ->route('admin.revision.show', ['id'=>base64_encode($revisionID)])
                    ->with('error', 'Note should not be empty!');
            $revision_note = new RevisionNote(array(
                'revision_id'       => $revisionID,
                'admin_id'          => $admin_id,
                'note'              => $note,
            ));
            if($revision_note->save()){
                if($revision->status == 'forward') {
                    $settyEmails = ['test@test.test'];
                    if (count($settyEmails)) {
                        $data = array(
                            'job_number' => $revision->job_number,
                            'Comment' => $note,
                        );
                        \Mail::send('emails.NewCommentAdded', $data, function ($message) use ($settyEmails) {
                            $message->from('test@test.test', "test");
                            $message->subject("New Comment Added");
                            $message->to($settyEmails);
                        });
                    }
                }
                return redirect()
                    ->route('admin.revision.show', ['id'=>base64_encode($revisionID)])
                    ->with('success', 'Note is successfully submitted!');
            }else{
                return redirect()
                    ->route('admin.revision.show', ['id'=>base64_encode($revisionID)])
                    ->with('error', 'Submission failed!');
            }
        }else if($request->has( 'revision_submit' )){
            $this->validate($request, [
                'email' => 'required',
                'file' => 'max:32768'
            ]);

            $order_id = $request->has( 'order_id' ) ? $request->order_id : '';
            $second_email = $request->has( 'second_email' ) ? $request->second_email : [];
            $second_email = $this->emailsToStr($second_email);
            if($request->has( 'order_id') && $request->order_id != null){
                $order = Order::find($request->order_id);
                if (!is_null($revision->order_id_legacy)){
                    $revision->job_number = $this->createRevisionJobNumber(null,
                        $revision->revision_number,
                        $revision->order_id_legacy
                    );
                }else{
                    $revision->job_number = $order->job_number;
                }
            }

            if($request->has( 'order_id_legacy') && $request->order_id_legacy != null && $request->order_id == null){
                $revisionNumber = $this->getRevisionNumber('', $request->order_id_legacy, $revisionID);
                $revision->revision_number = $revisionNumber;

                $posRevWord = strpos(strtolower($request->order_id_legacy), 'rev');
                $doesNotContainsRevWord = $posRevWord === false;
                if ($doesNotContainsRevWord){
                    $revision->job_number = $this->createRevisionJobNumber(null,
                        $revisionNumber,
                        $request->order_id_legacy
                    );
                }else{
                    if ($revisionNumber==1){
                        $revision->job_number = $request->order_id_legacy;
                    }else{
                        $reivisonWithoutRev = substr(
                            $request->order_id_legacy,
                            0,
                            $posRevWord-1
                        );
                        $revisionNumber = $this->getRevisionNumber('', $reivisonWithoutRev, $revisionID);
                        $revision->job_number = $this->createRevisionJobNumber(null,
                            $revisionNumber,
                            $reivisonWithoutRev
                        );
                    }
                }
            }

            $revision->order_id = $order_id;
            $revision->order_id_legacy = $request->has( 'order_id_legacy' ) ? $request->order_id_legacy : '';
            $revision->revision_type = $request->has( 'revision_type' ) ? $request->revision_type : '';
            $revision->email = $request->has( 'email' ) ? $request->email : '';
            $revision->second_email = $second_email;
            $revision->project_name = $request->has( 'project_name' ) ? $request->project_name : '';
            $revision->project_owners_street_address = $request->has( 'project_owners_street_address' ) ? $request->project_owners_street_address : '';
            $revision->status = $request->has( 'status' ) ? $request->status : OrderRevision::STATUS_NEW_REVISION;
            $revision->updated_by = $admin->id;

            if ($request->has('payment_status')) {
                $revision->payment_status = $request->payment_status;
            }
            if ($request->has('transaction_id')) {
                $revision->transaction_id = $request->transaction_id;
            }

            $revision->save();
            $revision_id = $revision->id;
            if ( $request->hasFile( 'file' ) ) {
                $files = $request->file( 'file' );
                $s3_folder = $revision->s3_folder;
                foreach ( $files as $file ) {
                    $result = null;
                    try {
                        $result = $this->s3Service->putFile('revision', $s3_folder, $file);
                    } catch (Exception $e) {
                    }
                    if (!is_null($result)) {
                        $images_array = array(
                            'revision_id'  => $revision_id,
                            'file_name' => $result['file_name'],
                            'org_file_name' => $result['org_file_name'],
                            'user_id' => $admin->id,
                            'user_type' => 2
                        );
                        RevisionFiles::create($images_array);
                    }
                }
            }
        }

        $log->logdata(__LINE__, __FILE__, __DIR__, __FUNCTION__, __CLASS__, __METHOD__, __NAMESPACE__,"Revision Updated by admin ID ". $adminId, null,'Revision'.var_export($revision, TRUE));

        return redirect()
            ->route('admin.revision.show', ['id' => base64_encode($revision->id)])
            ->with('success', 'Revision updated successfully');
    }
}