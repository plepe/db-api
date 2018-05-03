const DBApiTableCache = require('./DBApiTableCache')

class DBApiTable {
  constructor (spec) {
    this.spec = spec
    this.id = spec.id

    this.id_field = this.spec.id_field
    this.old_id_field = this.spec.old_id_field

    this.cache = new DBApiTableCache(this)
  }
}

module.exports = DBApiTable
