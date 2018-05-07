const DBApiTableCache = require('./DBApiTableCache')

class DBApiTable {
  constructor (schema) {
    this.schema = schema
    this.id = schema.id

    this.id_field = this.schema.id_field
    this.old_id_field = this.schema.old_id_field

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
    this.schema.fields[def.id] = def
    this.funFields.push(def)
  }
}

module.exports = DBApiTable
