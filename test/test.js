var url = 'http://localhost/~skunk/db-api/db.php'

var assert = require('assert')
var DBApi = require('../src/DBApi')
var api = new DBApi(url)

describe('DBApi.do', function () {
  it('should return something', function (done) {
    api.do(
      [
        { table: 'test2' }
      ],
      function (err, result) {
        console.log(JSON.stringify(result, null, '  '))
        assert.equal(!!result, true)
        done(err)
      }
    )
  })
})
