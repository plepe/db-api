class DBApiTable {
  constructor (spec) {
    this.spec = spec
    this.id = spec.id

    this.id_field = this.spec.id_field
    this.old_id_field = this.spec.old_id_field
  }
}

module.exports = DBApiTable
