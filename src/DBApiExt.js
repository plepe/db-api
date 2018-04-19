class DBApiExt {
  constructor (view, options) {
    this.view = view
    this.api = view.api
    this.options = options
  }
}

module.exports = DBApiExt
