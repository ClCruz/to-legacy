const environment = json.env;

const config = {
  api: json.api,
  apikey: json.apikey,
  setapikey,
}

function setapikey() {
  Vue.http.interceptors.push((request, next) => {
      if (request.url.startsWith(config.api)) {
        if (request.url.indexOf("apikey=")==-1) {
          if (request.url.indexOf("?")>-1) {
              request.url+="&apikey="+config.apikey;
            }
            else {
              request.url+="?apikey="+config.apikey;
            }
          }
      }
      next();
  });

}