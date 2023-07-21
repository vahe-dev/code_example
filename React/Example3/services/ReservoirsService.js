import axios from 'axios'
import request from '../helpers/utils/request';

export const ReservoirsService = {
    getGroups: () => {
        return request('GET', '/groups');
    },
    getAllCompanies: () => {
        return request('GET', '/admin/companies');
    },
    getAllCompaniesWithGroupsCount: () => {
        return request('GET', '/admin/companies/companies-with-groups-count', null,{limit:10000});
    },
    getAllGroupsList: () => {
        return request('GET', '/admin/groups', null,{limit:10000});
    },
    deleteGroup: (ids) => {
        return request('DELETE',`/admin/companies/bulkDelete`, ids);
    },
};
