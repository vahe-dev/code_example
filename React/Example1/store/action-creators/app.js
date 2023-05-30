import * as actionTypes from '../action-types/app';
import {ReservoirsService} from '../../services/ReservoirsService';
import {toast} from "react-toastify";

export const saveActiveReservoir = (payload) => ({
    type: actionTypes.SAVE_ACTIVE_RESERVOIR,
    payload,
});
export const setMetricsForGraphIsOpen = (payload) => ({
    type: actionTypes.SET_METRIC_FOR_GRAPH_IS_OPEN,
    payload,
});

export const getAllCompanies = () => {
    return async (dispatch) => {
        try {
            const responseData = await ReservoirsService.getAllCompanies();
            if (responseData.data.error) {
                throw new Error(responseData.error);
            }
            dispatch(saveAllCompanies(responseData.data))
        } catch(e) {
            console.log(e)
        }
    };
};

//Companies list with groups count
export const companiesWithGroupsCount = () => {
    return async (dispatch) => {
        try {
            const { data: { data, success, message } } = await ReservoirsService.getAllCompaniesWithGroupsCount();

            if (!success) {
                throw new Error(message);
            }
            dispatch(saveCompaniesWithGroups(data))
        } catch(e) {
            console.log(e)
        }
    };
};

export const saveGroups = (payload) => ({
    type: actionTypes.SAVE_GROUP,
    payload,
});

//Groups list
export const getAllGroupsList = () => {
    return async (dispatch) => {
        try {
            const { data: { data, success, message } } = await ReservoirsService.getAllGroupsList();
            if (!success) {
                throw new Error(message);
            }
            dispatch(saveGroups(data.data))
        } catch(e) {
            console.log(e)
        }
    };
};

export const deleteGroup = (ids) => {
    return async (dispatch) => {
        try {
            const responseData = await ReservoirsService.deleteGroup(ids);
            if (responseData.data.error) {
                throw new Error(responseData.error);
            }
            dispatch(getAllGroupsList());
            toast.success('Ջնջված է');
        } catch(e) {
            console.error(e)
            toast.error('Գործողությունը չհաջողվեց');
        }
    };
};
