const httpGetJSON = require('./httpGetJSON')

class DBApi {
  constructor (url, options) {
    this.url = url
    this.options = options
  }

  do (actions, callback) {
    httpGetJSON(
      'POST',
      this.url,
      JSON.stringify(actions),
      (err, result) => {
        if (err) {
          return callback(err, null)
        }

        if ('error' in result) {
          return callback(result.error, null)
        }

        return callback(null, result)
      }
    )
  }
}

module.exports = DBApi
