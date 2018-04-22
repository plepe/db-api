const XMLHttpRequest = require('w3c-xmlhttprequest').XMLHttpRequest
const httpGet = require('./httpGet')

function httpGetJSON (method, url, data, callback) {
  httpGet (method, url, data, (err, result) => {
    if (!err) {
      result = JSON.parse(result)
    }

    callback(err, result)
  })
}

module.exports = httpGetJSON
