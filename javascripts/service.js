
define(function (require) {
    const axios = require('axios');
    alert(axios);
});

// require(['axious'], function (axios) {
//     //foo is now loaded.
// });
//const axios = require('axios');

//const axios = require('/javascripts/axios.min.js')
//import config from '../config'

// const apiService = axios.create({
//   baseURL: '/admin/v2/api/', //'config.server',
//   timeout: 10000,
//   withCredentials: false
// });

const methodsService = {
  configToken () {
    apiService.defaults.headers.common['token'] = this.$store.getters.token;
  },
  post (url, send, success, fail) {
    apiService.post(url, send)
      .then(res => {
        console.log(res);
        success(res);
      })
      .catch(error => { 
        console.log(error);
        fail(error);
      });
  },
  put (url, send, success, fail) {
    apiService.put(url, send)
      .then(res => {
        console.log(res);
        success(res);
      })
      .catch(error => { 
        console.log(error);
        fail(error);
      });
  }, 
  delete (url, send, success, fail) {
    apiService.delete(url, send)
      .then(res => {
        console.log(res);
        success(res);
      })
      .catch(error => { 
        console.log(error);
        fail(error);
      });
  }, 
  get (url, send, success, fail) {
    console.log('service/index.js/get');
    apiService.get(url, send)
      .then(res => {
        console.log(res);
        success(res);
      })
      .catch(error => { 
        console.log(error);
        fail(error);
      });
  }, 
};

apiService.methods = methodsService;

//export default instance;