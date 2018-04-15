const DBApiView = require('./DBApiView')

class DBApiViewJSON extends DBApiView {
  show (callback) {
    this.get((err, result) => {
      if (err) {
        this.emit('show', {
          error: err
        })
        return callback(err)
      }

      let renderedResult = JSON.stringify(result, null, '    ')
      callback(null, renderedResult)

      this.emit('show', {
        result: renderedResult,
        error: null
      })
    })
  }
}

module.exports = DBApiViewJSON
