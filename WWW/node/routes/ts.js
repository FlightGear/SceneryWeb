var express = require('express');
var router = express.Router();
var dns = require('dns');

/* GET users listing. */
router.get('/status/:id?', function(req, res, next) {
  res.setHeader('Content-Type', 'text/html; charset=utf-8');

  var dnsname = "terrasync.flightgear.org";

  dns.resolve(dnsname, "NAPTR", function(err,addresses) {
    if( err ) {
      console.log(err);
      res.render('error', {} );
      return;
    } 

    res.render('tsstatus', {
      title: "Terrasync Status",
      dns: addresses,
      domainname: dnsname,
    });

    if( addresses ) addresses.forEach( function(address) {
      console.log(address);
    });
  });

});

module.exports = router;

