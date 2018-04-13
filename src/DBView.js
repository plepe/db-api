class DBView {
  constructor (dbApi, def, options) {
    this.api = dbApi
    this.def = def
    this.options = options
  }

  set_query (query) {
    this.query = query
  }

  get (callback) {
    this.api.do([ this.query ], (err, result) => {
      if (err) {
        return callback(err)
      }

      callback(null, result[0])
    })
  }

  show (callback) {
    this.get((err, result) => {
      if (err) {
        return callback(err)
      }

      callback(null, JSON.stringify(result))
    })
  }
}

module.exports = DBView
