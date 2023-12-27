import axios from 'axios';
import moment from "moment/moment";

export default class EventService {

  getEvents(start, end, categories = []) {
    if (start === end) {
      return Promise.resolve([]);
    }

    const categoryParams = categories.length > 0 ? '/' + categories.join(',') : '';
    const url = 'http://yusaopeny.docksal.site/schedules/get-event-data-date-range/West YMCA/' + moment(start).unix() + '/' + moment(end).unix() + categoryParams;

    return axios.get(url)
      .then(res => {

        console.log('res data from event service', res.data);

        return res.data.map(event => ({
          id: event.nid,
          title: event.name,
          start: event.time_start_calendar,
          end: event.time_end_calendar
        }));
      });
  }
}
