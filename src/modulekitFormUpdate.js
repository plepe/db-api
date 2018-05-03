function modulekitFormUpdate (def, api, tableId, callback) {
  let todoQuery = []
  let todoFun = []
  let table = api.getTable(tableId)

  handleValuesQueries(def, table.spec, todoQuery, todoFun)
  checkFormRights(def, table.spec)

  if (todoQuery.length === 0) {
    return callback(null)
  }

  api.do(todoQuery, (err, result) => {
    if (err) {
      return callback(err)
    }

    for (var i = 0; i < todoQuery.length; i++) {
      todoFun[i](result[i])
    }

    callback(null)
  })
}

function handleValuesQueries (def, table, todoQuery, todoFun) {
  if (typeof def !== 'object') {
    return
  }

  if ('values_query' in def) {
    todoQuery.push(def.values_query)
    delete def.values_query
    todoFun.push(result => {
      def.values = result
      def.values_mode = 'property'
      def.values_property = table.id_field || 'id'
    })
  } else {
    for (var k in def) {
      handleValuesQueries(def[k], table.fields[k], todoQuery, todoFun)
    }
  }
}

function checkFormRights (def, rights) {
  for (var k in def) {
    if (k === 'id') {
    }
    else if (!(k in rights.fields)) {
      delete def[k]
    }
    else if (rights.fields[k].type === 'sub_table') {
      checkFormRights(def[k].def.def, rights.fields[k])
    }
    else {
      def[k].may_read = ('read' in rights.fields[k] ? rights.fields[k].read : true)
      def[k].may_write = ('write' in rights.fields[k] ? rights.fields[k].write : false)

      if (rights.fields[k].write !== true) {
        def[k].type = 'label'
        def[k].include_data = false
        def[k].disabled = true
      }
    }
  }
}

module.exports = modulekitFormUpdate
