const httpGetJSON = require('./httpGetJSON')

let viewTypes = {
  'Base': require('./DBApiView'),
  'JSON': require('./DBApiViewJSON'),
  'Twig': require('./DBApiViewTwig'),
  'ModulekitForm': require('./DBApiViewModulekitForm')
}

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

  createView (type, def, options) {
    if (!(type in viewTypes)) {
      throw new Error('db-api view type ' + type + ' not defined!')
    }

    return new viewTypes[type](this, def, options)
  }
}

module.exports = DBApi
