import {FILE_INFOS_BY_GROUP, AVAILABLE_GROUPS_BY_USER_TYPE, USER_TYPES} from '../helpers/constants';
import * as actions from '../store/action-creators/app';
import {userSelector} from '../store/selectors/app';
import store from '../store';

const AppController = {};

AppController.initApp = async () => {
    const availableFileInfosByGroups = getAvailableFileInfosByGroups();

    Promise.all(
        Object.entries(availableFileInfosByGroups).map(([groupName, groupFileInfos]) => {
            return Promise.all(
                Object.entries(groupFileInfos).map(([reservoirName, {deviceId, serialNo, fileName, latitude, longitude, folderPath = 'Vorogum'}]) => {
                    const contentStr = `${groupName}\n${reservoirName}\n${deviceId}\n${serialNo}\n${latitude}\n${longitude}`;
                    if (! fileName) {
                        return Promise.resolve(`${contentStr}\n `);
                    }
                    const fetchUrl = process.env.env === 'production' ? `https://${process.env.ftpHost}` : `../..`;
                    return fetch(`${fetchUrl}/content/${folderPath ? folderPath  : 'Vorogum'}/${fileName}.txt`)
                        .then(async (res) => {
                            const text = await res.text();
                            if (! text.startsWith('<!DOCTYPE html>')) {
                                return `${contentStr}\n${text}`;
                            }
                            return `${contentStr}\n `;
                        });
                })
            );
        })
    ).then(reservoirsInfoByGroups => {
        const reservoirs = processReservoirs(reservoirsInfoByGroups);
        const reservoirsByGroups = getGroupedReservoirs(reservoirs);

        store.dispatch(actions.saveReservoirsByGroups(reservoirsByGroups));
    });
};

const getAvailableFileInfosByGroups = () => {
    const {type} = userSelector(store.getState());

    if (type === USER_TYPES.ADMIN) {
        return FILE_INFOS_BY_GROUP;
    }

    const availableFileInfosByGroups = {};

    AVAILABLE_GROUPS_BY_USER_TYPE[type].forEach(groupName => {
        availableFileInfosByGroups[groupName] = FILE_INFOS_BY_GROUP[groupName];
    });

    return availableFileInfosByGroups;
};

const getGroupedReservoirs = (reservoirs) => {
    const reservoirsByGroups = {};

    reservoirs.forEach(reservoir => {
        const groupName = reservoir.groupName;

        if (!reservoirsByGroups[groupName]) {
            reservoirsByGroups[groupName] = [];
        }
        reservoirsByGroups[groupName].push(reservoir);
    });

    return reservoirsByGroups;
};

const processReservoirs = (reservoirsByGroups) => {
    const result = [];

    reservoirsByGroups.forEach(reservoirsByGroup => {
        result.push(...reservoirsByGroup.map(reservoir => {
            const rows = reservoir.split('\n');

            return createReservoirInfo(rows);
        }));
    });

    return result;
};

const createReservoirInfo = (rows) => {
    const reservoirInfo = {};

    reservoirInfo.groupName = rows[0];
    reservoirInfo.name = rows[1];
    reservoirInfo.deviceId = rows[2];
    reservoirInfo.serialNo = rows[3];
    reservoirInfo.latitude = +rows[4];
    reservoirInfo.longitude = +rows[5];
    reservoirInfo.deviceCode = rows[6].split('m')[0] ? +rows[6].split('m')[0] : +rows[6];
    reservoirInfo.deviceType = +rows[6].split('m')[0];
    reservoirInfo.width = +rows[7]?.split('m')[0];
    reservoirInfo.maxHeight = +rows[8]?.split('m')[0];
    reservoirInfo.data = processData(rows.slice(9), reservoirInfo.serialNo, reservoirInfo.maxHeight, reservoirInfo.width, reservoirInfo.deviceCode);

    return reservoirInfo;
};

let lastHeight = 0
const processData = (data, serialNo, maxHeight, width, deviceCode) => {

    const dataItems = [];

    data.forEach((dataItem) => {

        const itemStr = dataItem
            .trim()
            .replace(/,|"|c+/g, '')
            .replace(/  +/g, ' ');

        if (/^(\d+)?m(\d+)\s(\d+)?mv\s(\d+)$/.test(itemStr) || (/^(\d+\.\d+)?m(\d+)\s(\d+)?mv\s(\d+)$/.test(itemStr))) {
            dataItems.push(itemStr);
        } else if (/^(\d+)m$/.test(itemStr) || /^(\d+)$/.test(itemStr) || /^[-+](\d+)m$/.test(itemStr)) { // to push height values too, if 'm' not exists
            dataItems.push(itemStr);
        } else {
            const regex = /((\d+)?m)\s(\d{4}-\d{1,2}-\d{1,2})\s(\d{1,2}:\d{1,2}:\d{1,2})\s(\d+)?mv\s(\d+)?/gm;
            let match;

            while ((match = regex.exec(itemStr)) !== null) {
                const parts = match[0].split(' ');
                const firstPart = parts[0].split('m');

                if (firstPart[0]) {
                    dataItems.push(match[0]);
                }
            }
        }
    });

    let lastDate;
    return dataItems.map((dataItem) => {

        const itemStr = dataItem.trim().replace(/  +/g, ' ');
        const res = {};
        const parts = itemStr.split(' ');
        const firstPart = parts[0].split('m');
        const nowDateTime = new Date();

        if (/^(\d+)?m(\d+)\s(\d+)?mv\s(\d+)$/.test(itemStr)) { // example: 80m090822078 3996mv 10

            if (firstPart[1]) {
                lastDate = getDate(firstPart[1]);
                res.date = lastDate;
                res.height = +firstPart[0];
                if (parts.length > 1) {
                    res.battery = +parts[1].split('mv')[0] === 0 ? 4200 : +parts[1].split('mv')[0]; // if mv=0, it means the battery is full(4200) charged
                    res.ping = +parts[2];
                }
            }
        } else if ((/^(\d+\.\d+)?m(\d+)\s(\d+)?mv\s(\d+)$/.test(itemStr))) { // example: 1.8m0907221135 4054mv c 24

            if (+parts[0].split('m')[0] !== 0 && +parts[0].split('m')[0] >= lastHeight) {
                if (firstPart[1]) {
                    lastDate = getDate(firstPart[1]);
                    res.date = lastDate;
                    res.height = +firstPart[0];
                    if (parts.length > 1) {
                        res.battery = +parts[1].split('mv')[0] === 0 ? 4200 : +parts[1].split('mv')[0]; // if mv=0, it means the battery is full(4200) charged
                        res.ping = +parts[2];
                    }
                }
            }
        } else if ((/^(\d+)$/.test(itemStr) || /^(\d+)m$/.test(itemStr) || /^[-+](\d+)m$/.test(itemStr)) && (!lastDate || nowDateTime >= lastDate)) {

            lastDate = lastDate ? new Date(lastDate.getTime() + 1800000) : nowDateTime;
            res.date = lastDate;
            res.height = +firstPart[0];
            res.battery = null;
            res.ping = null;
        } else if (/^((\d+)?m)\s(\d{4}-\d{1,2}-\d{1,2})\s(\d{1,2}:\d{1,2}:\d{1,2})\s((\d+)?mv)\s(\d+)$/.test(itemStr)) { // example: 137m 2023-2-25 15:49:00 4158mv 25

            res.height = +parts[0].split('m')[0]; // 137
            lastDate = parts[1] + ' ' + parts[2]; // 2023-2-25 15:49:00
            lastDate = getDateParts(lastDate);
            res.date = lastDate;
            res.battery = +parts[3].split('mv')[0] === 0 ? 4200 : +parts[3].split('mv')[0]; // if mv=0, it means the battery is full(4200) charged
            res.ping = +parts[4];
        }

        return res;
    }).filter(item => item.date);
};

const getDateParts = (dateInfo) => {
    const dateParts = dateInfo.split(" ")[0].split("-");
    const timeParts = dateInfo.split(" ")[1].split(":");

    const year = dateParts[0];
    const month = dateParts[1] -1;
    const day = dateParts[2];

    const hour = timeParts[0];
    const minute = timeParts[1];
    // const second = timeParts[2];

    return new Date(year, month, day, hour, minute);
}

const getDate = (dateInfo) => {
    const dateStr = dateInfo.substr(0, 6);
    const timeStr = dateInfo.substr(dateInfo.length === 10 ? -4 : -3);

    const year = +`20${dateStr.substr(4, 2)}`;
    const month = dateStr.substr(2, 2) - 1;
    const day = +dateStr.substr(0, 2);
    const hour = +timeStr.substr(0, 2);
    const min = +timeStr.substr(2, dateInfo.length === 10 ? 2 : 1);

    return new Date(year, month, day, hour, min);
};

AppController.destroy = () => {
};

export default AppController;
