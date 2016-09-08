var express = require('express');
var router = express.Router();

/* GET home page. */
router.get('/', function(req, res, next) {
  res.setHeader('Content-Type', 'text/html; charset=utf-8');
  res.render('index' );
});

module.exports = router;
