const DBView = require('./DBView')

class DBViewJSON extends DBView {
  show (callback) {
    this.get((err, result) => {
      if (err) {
        return callback(err)
      }

      callback(null, JSON.stringify(result, null, '    '))
    })
  }
}

module.exports = DBViewJSON
