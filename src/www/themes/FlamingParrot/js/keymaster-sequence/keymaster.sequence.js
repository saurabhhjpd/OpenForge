//     keymaster.sequence.js
//     (c) 2011 Ron Derksen
//     keymaster.sequence.js may be freely distributed under the MIT license.
;(function (keymaster) {
  _seqScope = "seq_";

  if (!Function.prototype.bind) {
      Function.prototype.bind = function (obj) {
          // closest thing possible to the ECMAScript 5 internal IsCallable function
          if (typeof this !== 'function') {
              throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
          }
          var slice = [].slice,
              args = slice.call(arguments, 1),
              self = this,
              nop = function () { },
              bound = function () {
                  return self.apply(this instanceof nop ? this : (obj || {}),
                                    args.concat(slice.call(arguments)));
              };
          bound.prototype = this.prototype;
          return bound;
      };
  }

  keymaster.sequence = function (keys, scope, method) {
    if (method == undefined) {
      method = scope;
      scope = 'all';
    }

    var _origScope = scope;
    for(i = 0; i < keys.length; i++) {
      if (i < keys.length-1) {
        //create specific scope for current key in sequence
        _seqScope = _seqScope + keys[i];

        keymaster(keys[i], scope, function (ev, key) {
          keymaster.setScope(this.toString());

          // reset scope after 1 second
          _timer = setTimeout(function () {
            keymaster.setScope(_origScope);
          }, 1000);
        }.bind(_seqScope));
      } else {
        // last key should perform the method
        keymaster(keys[i], _seqScope, function() {
          // Set the scope back to the original, then
          // call our passed in method
          keymaster.setScope(_origScope);
          return method.apply(this, arguments);
        });
      }
      scope = _seqScope;
    }
    // reset _seqScope for new sequence
    _seqScope = "seq_";
  }
})(key);
