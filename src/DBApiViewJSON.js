const DBApiView = require('./DBApiView')

class DBApiViewJSON extends DBApiView {
  show (callback) {
    this.get((err, result) => {
      if (err) {
        return callback(err)
      }

      callback(null, JSON.stringify(result, null, '    '))
    })
  }
}

module.exports = DBApiViewJSON
