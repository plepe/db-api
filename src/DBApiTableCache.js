class DBApiTableCache {
  constructor (table) {
    this.entryCache = {}
    this.table = table
  }

  get (id) {
    if (id in this.entryCache) {
      return this.entryCache[id]
    }

    return undefined
  }

  addToCache (result) {
    let id_field = this.table.spec ? this.table.spec.id_field || 'id' : 'id'

    for (var k in result) {
      this.entryCache[result[k][id_field]] = result[k]
    }
  }
}

module.exports = DBApiTableCache
