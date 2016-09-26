// load all the things we need
var GoogleStrategy = require('passport-google-oauth').OAuth2Strategy;
var GitHubStrategy = require('passport-github2').Strategy;
var OAuth1Strategy = require('passport-oauth1').Strategy;

var bcrypt   = require('bcrypt-nodejs');
var DB = require('../config/database');

var configAuth = require('./auth');

// expose this function to our app using module.exports
module.exports = function(passport) {

    passport.serializeUser(function(user, done) {
        done(null, user.authorities[0] );
    });

    passport.deserializeUser(function(authority, done) {
        DB.GetAuthorByExternalId( authority.id, authority.user_id, function(err,user) {
            done(err, user);
        });
    });

    passport.use(new OAuth1Strategy({
      requestTokenURL: configAuth.sourceforgeAuth.requestTokenURL,
      accessTokenURL: configAuth.sourceforgeAuth.accessTokenURL,
      userAuthorizationURL: configAuth.sourceforgeAuth.userAuthorizationURL,
      consumerKey: configAuth.sourceforgeAuth.consumerKey,
      consumerSecret: configAuth.sourceforgeAuth.consumerSecret,
      callbackURL: configAuth.sourceforgeAuth.callbackURL,
//      signatureMethod: "RSA-SHA1"
    },
    function(token, tokenSecret, profile, done) {
console.log("sourceforge profile:",profile);
      done("not implemented");
    }
));

    passport.use(new GoogleStrategy({

        clientID        : configAuth.googleAuth.clientID,
        clientSecret    : configAuth.googleAuth.clientSecret,
        callbackURL     : configAuth.googleAuth.callbackURL,

    },
    function(token, refreshToken, profile, done) {
        process.nextTick(function() {
            DB.getOrCreateUserByExternalId( 'google', profile.id, function(err,user) {
                if (err) return done(err);
                if (user) return done(null, user);
                done("Barf - where is our google user?");
            });
        });
    }));

    passport.use(new GitHubStrategy({

        clientID        : configAuth.githubAuth.clientID,
        clientSecret    : configAuth.githubAuth.clientSecret,
        callbackURL     : configAuth.githubAuth.callbackURL,

    },
    function(token, refreshToken, profile, done) {
        process.nextTick(function() {
            DB.getOrCreateUserByExternalId( 'github', profile.username, function(err,user) {
                if (err) return done(err);
                if (user) return done(null, user);
                done("Barf - where is our github user?");
            });
        });
    }));

};
