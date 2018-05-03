const DBApiTableCache = require('./DBApiTableCache')

class DBApiTable {
  constructor (spec) {
    this.spec = spec
    this.id = spec.id

    this.id_field = this.spec.id_field
    this.old_id_field = this.spec.old_id_field

    this.cache = new DBApiTableCache(this)

    this.funFields = []
  }

  clearCache () {
    this.cache = new DBApiTableCache(this)
  }

  updateFields (entry) {
    this.funFields.forEach((def) => {
      entry[def.id] = def.fun(entry)
    })
  }

  addField (def) {
    this.spec.fields[def.id] = def
    this.funFields.push(def)
  }
}

module.exports = DBApiTable
