import axios from 'axios';

export function autoRefreshCsrfToken() {
  axios.interceptors.response.use();

  axios.interceptors.response.use(
    (response) => response,
    async (err) => {
      const status = err.response?.status;

      if (status === 419) {
        await axios.post('/csrf-token');

        return axios(err.response.config);
      }

      return Promise.reject(err);
    },
  );
}
