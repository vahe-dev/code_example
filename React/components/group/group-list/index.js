import React, {useEffect, useState} from 'react';
import Button from "../../../shared/Button/Button";
import plus from "../../../../../assets/resources/global/plus.svg";
import DataTable from "react-data-table-component";
import {useDispatch, useSelector} from "react-redux";
import {useHistory} from "react-router-dom";
import '../../../../../assets/scss/desktop/_group-list.scss';
import edit from "../../../../../assets/resources/global/pencil.svg";
import DeleteSelectedGroupRows from "./components/DeleteSelectedGroupRows";
import {getAllGroupsList} from "../../../../store/action-creators/app";
import {groupsListSelector} from "../../../../store/selectors/app";

const GroupList = () => {
    const dispatch = useDispatch();
    const [searchText, setSearchText] = useState('');
    const history = useHistory();
    const [filterValue, setFilterValue] = useState('all');
    const [selectedRows, setSelectedRows] = useState([]);
    const handleChange = (row) => {
        setSelectedRows(row.selectedRows);
    };


    useEffect(() => {
        dispatch(getAllGroupsList());
        // dispatch(getAllReservoirsList());
        // dispatch(getAllCompanies());
    }, [])
    const groupsList = useSelector(groupsListSelector);

    // const allCompanies = useSelector(getAllCompaniesSelector);
    // const allReservoirsLoading = useSelector(getAllReservoirsLoadingSelector)
    const addNew = () => {
        history.push('/group/create');
    }
    const handleEdit = (data) => {
        history.push( `/group/edit/${data.id}`);
    }
    const customStyles = {
        cells: {
            style: {
                border: '1px solid #ddd',
            },
        },
        headCells: {
            style: {
                border: '1px solid #ddd',
                fontWeight: '800',
                color: '#000',
            },
        },
    };

    const columns = [
        {
            name: 'Բաժանարար',
            selector: row => row.name,
        },
        {
            name: 'ՋՕԸ',
            selector: row => row.companyName,
        },
        {
            name: 'Օգտագործող',
            // selector: row => row.users,
            cell: (row) => <span>{row.users.map(e => e.name).join(', ')}</span>
        },
        {
            name: 'Խմբագրել',
            cell: (row) => <button onClick={() => handleEdit(row)}><img src={edit} width={'30%'}/></button>,
            button: true,
        },
    ];


    return (
        <div className='group-list-container'>
            <h2 className='group-list-title'>Բաժանարարներ</h2>
            <div className='group-list-inputs'>
                <div className='group-list-add-buttons'>
                    <DeleteSelectedGroupRows selectedRows={selectedRows} />
                    <Button
                        variant={'primary'}
                        size={'xs'}
                        icon={plus}
                        onClick={addNew}
                    >
                        Ավելացնել
                    </Button>
                </div>

            </div>
            <div className='group-list-table-wrapper'>
                <div className='group-list-table'>

                    <DataTable
                        columns={columns}
                        data={groupsList}
                        selectableRows
                        customStyles={customStyles}
                        onSelectedRowsChange={handleChange}
                        pagination
                    />


                </div>
            </div>
        </div>
    )
};
export default GroupList;