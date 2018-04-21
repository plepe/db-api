const fs = require('fs')
const jsdom = require('jsdom')
global.document = (new jsdom.JSDOM('')).window.document

const DBApi = require('../src/DBApi')

const conf = JSON.parse(fs.readFileSync('test-conf.json', 'utf8'));

const assert = require('assert')
const twig = require('twig')

const api = new DBApi(conf.url, conf.options)
api.addTable({
  id: 'test3_nationality',
  id_field: 'code'
})

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
    view.set_query({ table: 'test2', query: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
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
    view.set_query({ table: 'test2', query: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ '1: 2\n' ])
      let expected = '<div>1: 2\n</div>'
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
    view.set_query({ table: 'test2', query: 1 })
    view.once('loadstart', ev => {
      stepDone()
    })
    view.once('loadend', ev => {
      stepDone()
    })
    view.once('show', ev => {
      assert.equal(ev.error, null, 'Error should be null')
      assert.deepEqual(ev.result, [ '1\n2\n' ])
      let expected = '<div>1\n2\n</div>'
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
      let expected = '<div>1: 2\n</div><div>2: 1\n</div><div class="loadMore"><a href="#">load more</a></div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()

      view.once('show', ev => {
        assert.equal(ev.error, null, 'Error should be null')
        assert.deepEqual(ev.result, [ '3: 2\n', '4: 2\n' ])
        let expected = '<div>1: 2\n</div><div>2: 1\n</div><div>3: 2\n</div><div>4: 2\n</div>'
        assert.equal(dom.innerHTML, expected)
        stepDone()
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
      let expected = '<div>1: 2\n</div><div>2: 1\n</div><div>3: 2\n</div><div>4: 2\n</div>'
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
      assert.deepEqual(ev.result, [ "Alice: Deutschland (de)", "Bob: Österreich (at)", "Conny: United Kingdom (uk)", "Dennis:  ()" ])

      let expected = '<div>Alice: Deutschland (de)</div><div>Bob: Österreich (at)</div><div>Conny: United Kingdom (uk)</div><div>Dennis:  ()</div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom, { step: 0 })
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
      assert.deepEqual(ev.result, [ 'Alice', 'Bob', 'Conny', 'Dennis' ])
      let expected = '<div>Alice<div>dummy</div></div><div>Bob<div>dummy</div></div><div>Conny<div>dummy</div></div><div>Dennis<div>dummy</div></div>'
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
      assert.deepEqual(ev.result, [ 'Alice', 'Bob', 'Conny', 'Dennis' ])
      let expected = '<div>Alice<div>dummy</div></div><div>Bob<div>dummy</div></div><div>Conny<div>dummy</div></div><div>Dennis<div>dummy</div></div>'
      assert.equal(dom.innerHTML, expected)
      stepDone()
    })
    view.show(dom)
  })
})
