import React, {useEffect, useState} from 'react';
import { useImmer } from 'use-immer';
import { useHistory, useParams } from 'react-router-dom'
import { useForm, FormProvider } from 'react-hook-form';
import Button from '../../../shared/Button/Button';
import back from '../../../../../assets/resources/global/back.svg';
import '../../../../../assets/scss/desktop/_group-edit.scss';
import FormSelect from "../../water-meter/water-meter-edit/components/form-select";
import {yupResolver} from "@hookform/resolvers/yup";
import {CREATE_GROUP_SCHEME} from "../../../../helpers/utils/validators";
import {ReservoirsService} from "../../../../services/ReservoirsService";
import {toast} from "react-toastify";
import AsyncSelect from 'react-select/async'

const GroupEdit = () => {
    const {id} = useParams();
    const history = useHistory();
    const [userOptions, setUserOptions] = useImmer({
        alreadyLoaded: false,
        options: [],
    })
    const methods = useForm({
        reValidateMode: 'onChange',
        resolver: yupResolver(CREATE_GROUP_SCHEME),
    });
    const userIds = methods.watch('user_ids');

    const cancel = () => {
        history.push('/groups');
    }

    const fetchUsers = async (inputValue) => {
        try {
            const isLoadedAlready = userOptions.alreadyLoaded;
            let options;
            if(!isLoadedAlready) {
                const { data: { data, success, message } } = await ReservoirsService.getUsersList();
                if(!success) {
                    throw new Error(message)
                }
                options = data.map(user => ({ label: user.name, value: user.id }));
                setUserOptions(_ => ({
                    alreadyLoaded: true,
                    options,
                }));
            } else {
                options = userOptions.options.filter((r) => r.label.toLowerCase().startsWith(inputValue))
            }
            return options;
        } catch (err) {
            console.error(err.message)
        }
    }

    const onUserChange = (value) => {
        methods.setValue('user_ids', value.map(e => e.value))
    }

    const save = async (data) => {
        try {
            const { data: { success, message }} = await ReservoirsService[id ? 'updateGroup' : 'createGroup']({ id, data });
            if(!success) {
                throw new Error(message);
            }
            toast.success(id ? 'Հաջողությամբ թարմացվել է' : 'Դուք ստեղծեցիք նոր բաժանարար');
            history.push('/groups');
        } catch (e) {
            toast.error('Գործողությունը չհաջողվեց');
        }
    }

    const getGroup = async () => {
        try {
            const { data: { data, success, message } } = await ReservoirsService.getGroupById(id)
            if(!success) {
                throw new Error(message);
            }
            methods.setValue('name', data.name);
            methods.setValue('parent_id', data.company.id);
            methods.setValue('user_ids', data.users.map(e => e.id));
        } catch (err) {
            toast.error('Տվյալների բեռնման սխալ');
        }
    }

    useEffect(() => {
        if(id) {
            getGroup();
        }
    }, [id]);

    return (
        <FormProvider {...methods}>
            <div className='group-edit-container'>
                <div className='group-edit-back'>
                    <a href='/groups'><img src={back} alt='back'/></a>
                </div>
                <h2 className='group-edit-title'>{id ? 'Խմբագրել' : 'Ավելացնել նոր բաժանարար'}</h2>
                <form onSubmit={methods.handleSubmit(save)}>
                    <div className='group-edit-content'>
                        <div className='group-edit-content-columns'>
                            <div className='group-edit-content-column'>
                                <div className='group-edit-content-item'>
                                    <label htmlFor='group'>ՋՕԸ</label>
                                    <div className='water-meter-edit-select-option'>
                                        <FormSelect
                                            name='parent_id'
                                            type='company'
                                        />
                                    </div>
                                </div>
                                <div className='group-edit-content-item'>
                                    <label htmlFor='group'>Օգտագործողներ</label>
                                    <AsyncSelect
                                        isMulti
                                        cacheOptions
                                        defaultOptions
                                        loadOptions={fetchUsers}
                                        onChange={onUserChange}
                                        placeholder='Ընտրել'
                                        value={userOptions.options.filter(e => userIds?.indexOf(e.value) > -1)}
                                    />
                                </div>
                                <div className='group-edit-content-item'>
                                    <label htmlFor='group'>Անվանումը</label>
                                    <div className='group-edit-content-item-input'>
                                        <input type='text' id='group' {...methods.register('name')} />
                                        {methods.formState.errors.name ? <span>{methods.formState.errors.name.message}</span> : null}
                                    </div>
                                </div>
                            </div>
                            <div className='group-edit-content-column'>
                            </div>
                        </div>
                        <div className='group-edit-content-buttons'>
                            <Button
                                variant={'dark'}
                                size={'xs'}
                                onClick={cancel}
                            >
                                Չեղարկել
                            </Button>
                            <Button
                                variant={'primary'}
                                size={'xs'}
                                type='submit'
                            >
                                {id ? 'Խմբագրել' : 'Ավելացնել'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </FormProvider>
    )
}
export default GroupEdit;