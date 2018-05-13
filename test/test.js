const fs = require('fs')
const jsdom = require('jsdom')
global.document = (new jsdom.JSDOM('')).window.document

const DBApi = require('../src/DBApi')

const conf = JSON.parse(fs.readFileSync('test-conf.json', 'utf8'));

const assert = require('assert')
const twig = require('twig')
const async = {
  parallel: require('async/parallel')
}

let api

describe('DBApi', function () {
  it('init', function (done) {
    api = new DBApi(conf.url, conf.options, (err) => done(err))
  })

  describe('do', function () {
    it('should return something', function (done) {
      api.do(
        [
          { table: 'test2' }
        ],
        function (err, result) {
          assert.equal(!!result, true)
          assert.deepEqual(result, [[{"id":1,"commentsCount":2,"comments":[{"test2_id":1,"id":2,"text":"foobar"},{"test2_id":1,"id":4,"text":"foobar2"}]},{"id":2,"commentsCount":1,"comments":[{"test2_id":2,"id":3,"text":"foobar"}]},{"id":3,"commentsCount":2,"comments":[{"test2_id":3,"id":5,"text":"foobar"},{"test2_id":3,"id":6,"text":"foobar2"}]},{"id":4,"commentsCount":2,"comments":[{"test2_id":4,"id":7,"text":"foobar"},{"test2_id":4,"id":8,"text":"foobar2"}]}]])
          done(err)
        }
      )
    })

    it('cache', function (done) {
      let actions = [
          { table: 'test2' }
        ]

      api.do(actions,
        function (err, result) {
          assert.equal(!!result, true)
          assert.deepEqual(result, [[{"id":1,"commentsCount":2,"comments":[{"test2_id":1,"id":2,"text":"foobar"},{"test2_id":1,"id":4,"text":"foobar2"}]},{"id":2,"commentsCount":1,"comments":[{"test2_id":2,"id":3,"text":"foobar"}]},{"id":3,"commentsCount":2,"comments":[{"test2_id":3,"id":5,"text":"foobar"},{"test2_id":3,"id":6,"text":"foobar2"}]},{"id":4,"commentsCount":2,"comments":[{"test2_id":4,"id":7,"text":"foobar"},{"test2_id":4,"id":8,"text":"foobar2"}]}]])
          assert.deepEqual(actions, [{ "table": "test2", "action": "nop", "cacheIndex": 0 }])
          done(err)
        }
      )
    })

    it('cache - accessing not loaded data twice in one do()', function (done) {
      api.clearCache()

      let actions = [
          { table: 'test2', id: 1 },
          { table: 'test2', id: 1 }
        ]

      api.do(actions,
        function (err, result) {
          assert.equal(!!result, true)
          assert.deepEqual(actions, [{ "table": "test2", "id": 1, "action": "select", "cacheIndex": 0 }, { "table": "test2", "id": 1, "action": "nop", "cacheIndex": 0 }])
          assert.deepEqual(result, [[{"id":1,"commentsCount":2,"comments":[{"test2_id":1,"id":2,"text":"foobar"},{"test2_id":1,"id":4,"text":"foobar2"}]}],[{"id":1,"commentsCount":2,"comments":[{"test2_id":1,"id":2,"text":"foobar"},{"test2_id":1,"id":4,"text":"foobar2"}]}]])
          done(err)
        }
      )
    })

    it('cache - accessing not loaded data trice', function (done) {
      api.clearCache()

      async.parallel([
        (done) => {
          let actions = [
              { table: 'test2', id: 1 },
              { table: 'test2', id: 1 }
            ]

          api.do(actions,
            function (err, result) {
              assert.equal(!!result, true)
              assert.deepEqual(actions, [{ "table": "test2", "id": 1, "action": "select", "cacheIndex": 0 }, { "table": "test2", "id": 1, "action": "nop", "cacheIndex": 0 }])
              assert.deepEqual(result, [[{"id":1,"commentsCount":2,"comments":[{"test2_id":1,"id":2,"text":"foobar"},{"test2_id":1,"id":4,"text":"foobar2"}]}],[{"id":1,"commentsCount":2,"comments":[{"test2_id":1,"id":2,"text":"foobar"},{"test2_id":1,"id":4,"text":"foobar2"}]}]])
              done(err)
            }
          )
        },
        (done) => {
          let actions = [
              { table: 'test2', id: 1 }
            ]

          api.do(actions,
            function (err, result) {
              assert.equal(!!result, true)
              assert.deepEqual(actions, [{ "table": "test2", "id": 1, "action": "nop", "cacheIndex": 0 }])
              assert.deepEqual(result, [[{"id":1,"commentsCount":2,"comments":[{"test2_id":1,"id":2,"text":"foobar"},{"test2_id":1,"id":4,"text":"foobar2"}]}]])
              done(err)
            }
          )
        }
      ],
      (err) => {
        done(err)
      })
    })
  })

  describe('getTable', function () {
    it('test2', function (done) {
      let table = api.getTable('test2')
      assert.equal(table.id, 'test2')
      done()
    })
  })

  describe('funFields', function () {
    it('test3 - capitalName', function (done) {
      let table = api.getTable('test3')
      table.addField({
        id: 'capitalName',
        type: 'fun',
        fun: (entry) => {
          return entry.name.toUpperCase()
        }
      })

      api.do(
        [
          { table: 'test3' }
        ],
        function (err, result) {
          assert.equal(!!result, true)
          assert.deepEqual(result, [[
            {"name":"Alice","age":40,"weight":50,"nationality":"de","capitalName":"ALICE"},
            {"name":"Bob","age":35,"weight":82,"nationality":"at","capitalName":"BOB"},
            {"name":"Conny","age":35,"weight":50,"nationality":"uk","capitalName":"CONNY"},
            {"name":"Dennis","age":67,"weight":68,"nationality":null,"capitalName":"DENNIS"},
            {"name":"Emily","age":17,"weight":52,"nationality":null,"capitalName":"EMILY"}
          ]])

          done(err)
        }
      )
    })
  })
})

describe('DBApiView', () => {
  it('init', () => {
    let view = api.createView({
      type: 'Base'
    })
  })

  it('show', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'Base'
    })
    let dom = document.createElement('div')
    let expected = '[{"a":2,"b":"bar","d":"b"},{"a":4,"b":"bla","d":"b"}]'
    view.set_query({ table: 'test1' })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.equal(ev.result, expected)
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.show(dom)
  })
})

describe('DBApiViewJSON', () => {
  it('init', () => {
    let view = api.createView({
      type: 'JSON'
    })
  })

  it('show', (done) => {
    let expected = `[
    {
        "id": 1,
        "commentsCount": 2,
        "comments": [
            {
                "test2_id": 1,
                "id": 2,
                "text": "foobar"
            },
            {
                "test2_id": 1,
                "id": 4,
                "text": "foobar2"
            }
        ]
    }
]`
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'JSON'
    })
    let dom = document.createElement('div')
    view.set_query({ table: 'test2', id: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null)
      assert.equal(ev.result, expected)
      stepDone()
    })
    view.show(dom)
  })
})

describe('DBApiViewTwig', () => {
  it('init', () => {
    let view = api.createView({
      type: 'Twig',
      each: ''
    }, { twig })
  })

  it('show', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: '{{ entry.id }}: {{ entry.commentsCount }}\n'
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test2', id: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ '1: 2\n' ])
      let expected = '<div class="entry">1: 2\n</div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom)
  })

  it('show (defined via array)', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: [ '{{ entry.id }}', '{{ entry.commentsCount }}\n' ]
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test2', id: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ '1\n2\n' ])
      let expected = '<div class="entry">1\n2\n</div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom)
  })

  it('show step', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 4) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: '{{ entry.id }}: {{ entry.commentsCount }}\n'
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test2' })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ '1: 2\n', '2: 1\n' ])
      let expected = '<div class="entry">1: 2\n</div><div class="entry">2: 1\n</div><div class="loadMore"><a href="#">load more</a></div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()

      view.once('show', ev => {
        assert.equal(ev.error, null, 'Error should be null')
        assert.deepEqual(ev.result, [ '3: 2\n', '4: 2\n' ])
        let expected = '<div class="entry">1: 2\n</div><div class="entry">2: 1\n</div><div class="entry">3: 2\n</div><div class="entry">4: 2\n</div>'
        assert.equal(dom.innerHTML, expected)
        stepDone()
      })
      ev.showMoreFunction()
    })
    view.show(dom, { step: 2 })
  })

  it('test3 - show step', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 5) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: '{{ entry.name }}'
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test3' })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ 'Alice', 'Bob' ])
      let expected = '<div class="entry">Alice</div><div class="entry">Bob</div><div class="loadMore"><a href="#">load more</a></div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()

      view.once('show', ev => {
        assert.equal(ev.error, null, 'Error should be null')
        assert.deepEqual(ev.result, [ 'Conny', 'Dennis' ])
        let expected = '<div class="entry">Alice</div><div class="entry">Bob</div><div class="entry">Conny</div><div class="entry">Dennis</div><div class="loadMore"><a href="#">load more</a></div>'
        assert.equal(dom.innerHTML, expected)
        stepDone()

        view.once('show', ev => {
          assert.equal(ev.error, null, 'Error should be null')
          assert.deepEqual(ev.result, [ 'Emily' ])
          let expected = '<div class="entry">Alice</div><div class="entry">Bob</div><div class="entry">Conny</div><div class="entry">Dennis</div><div class="entry">Emily</div>'
          assert.equal(dom.innerHTML, expected)
          stepDone()
        })

        ev.showMoreFunction()
      })
      ev.showMoreFunction()
    })
    view.show(dom, { step: 2 })
  })

  it('show all (step=0)', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: '{{ entry.id }}: {{ entry.commentsCount }}\n'
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test2' })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ '1: 2\n', '2: 1\n', '3: 2\n', '4: 2\n' ])
      let expected = '<div class="entry">1: 2\n</div><div class="entry">2: 1\n</div><div class="entry">3: 2\n</div><div class="entry">4: 2\n</div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom, { step: 0 })
  })

  it('show test3 (with dbApiGet)', (done) => {
    api.clearCache()
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: "{{ entry.name }}: {{ entry.nationality|dbApiGet('test3_nationality').name }} ({{ entry.nationality}})"
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test3' })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ "Alice: Deutschland (de)", "Bob: Österreich (at)", "Conny: United Kingdom (uk)", "Dennis:  ()", "Emily:  ()" ])

      let expected = '<div class="entry">Alice: Deutschland (de)</div><div class="entry">Bob: Österreich (at)</div><div class="entry">Conny: United Kingdom (uk)</div><div class="entry">Dennis:  ()</div><div class="entry">Emily:  ()</div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom, { step: 0 })
  })

  it('test3 - show step with pre/post', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 5) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: '{{ entry.name }}',
      pre: 'foo',
      post: 'bar'
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test3' })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ 'Alice', 'Bob' ])
      let expected = '<div class="pre">foo</div><div class="entry">Alice</div><div class="entry">Bob</div><div class="loadMore"><a href="#">load more</a></div><div class="post">bar</div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()

      view.once('show', ev => {
        assert.equal(ev.error, null, 'Error should be null')
        assert.deepEqual(ev.result, [ 'Conny', 'Dennis' ])
        let expected = '<div class="pre">foo</div><div class="entry">Alice</div><div class="entry">Bob</div><div class="entry">Conny</div><div class="entry">Dennis</div><div class="loadMore"><a href="#">load more</a></div><div class="post">bar</div>'
        assert.equal(dom.innerHTML, expected)
        stepDone()

        view.once('show', ev => {
          assert.equal(ev.error, null, 'Error should be null')
          assert.deepEqual(ev.result, [ 'Emily' ])
          let expected = '<div class="pre">foo</div><div class="entry">Alice</div><div class="entry">Bob</div><div class="entry">Conny</div><div class="entry">Dennis</div><div class="entry">Emily</div><div class="post">bar</div>'
          assert.equal(dom.innerHTML, expected)
          stepDone()
        })

        ev.showMoreFunction()
      })
      ev.showMoreFunction()
    })
    view.show(dom, { step: 2 })
  })

})

describe('DBApiExtDummy', () => {
  it('show', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: '{{ entry.name }}'
    }, { twig })
    view.extend({
      type: 'Dummy',
      text: 'dummy'
    })
    let dom = document.createElement('div')
    view.set_query({ table: 'test3' })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ 'Alice', 'Bob', 'Conny', 'Dennis', 'Emily' ])
      let expected = '<div class="entry">Alice<div>dummy</div></div><div class="entry">Bob<div>dummy</div></div><div class="entry">Conny<div>dummy</div></div><div class="entry">Dennis<div>dummy</div></div><div class="entry">Emily<div>dummy</div></div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom)
  })

  it('show', (done) => {
    let doneCount = 0
    function stepDone (err) {
      if (++doneCount >= 3) {
        done()
      }
    }

    let view = api.createView({
      type: 'Twig',
      each: '{{ entry.name }}',
      extensions: [
        {
          type: 'Dummy',
          text: 'dummy'
        }
      ]
    }, { twig })
    let dom = document.createElement('div')
    view.set_query({ table: 'test3' })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ 'Alice', 'Bob', 'Conny', 'Dennis', 'Emily' ])
      let expected = '<div class="entry">Alice<div>dummy</div></div><div class="entry">Bob<div>dummy</div></div><div class="entry">Conny<div>dummy</div></div><div class="entry">Dennis<div>dummy</div></div><div class="entry">Emily<div>dummy</div></div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom)
  })
})
