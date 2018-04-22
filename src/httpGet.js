const XMLHttpRequest = require('w3c-xmlhttprequest').XMLHttpRequest

function httpGet (method, url, data, callback) {
  var xhr = new XMLHttpRequest()
  xhr.onload = () => {
    let err, data

    if (xhr.status !== 200) {
      err = 'Status ' + xhr.status
    }

    callback(err, xhr.response)
  }

  xhr.open(method, url)
  xhr.send(data)
}

module.exports = httpGet
