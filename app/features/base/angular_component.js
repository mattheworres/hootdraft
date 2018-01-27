const Cls = (this.AngularComponent = class AngularComponent {
  static initClass() {

    //inject global dependencies
    this.inject();
  }
  static register(name, type) {
    if (name == null) { name = this.name || __guard__(this.toString().match(/function\s*(.*?)\(/), x => x[1]); }
    return __guardMethod__(angular.module('app'), type, (o, m) => o[m](name, this));
  }

  static inject(...args) {
    if (this.$inject) { args.push(...Array.from(this.$inject || [])); }
    return this.$inject = args;
  }

  constructor(...args) {
    //add all of the injected parameters into 'this'
    let key;
    for (let index = 0; index < this.constructor.$inject.length; index++) {
      key = this.constructor.$inject[index];
      this[key] = args[index];
    }

    //Expose members of the base class directly onto the child class unless it starts
    //  with an underscore, then it is private
    for (key in this.constructor.prototype) {
      const fn = this.constructor.prototype[key];
      if (typeof fn !== 'function') { continue; }
      if (['constructor', 'initialize'].includes(key) || (key[0] === '_')) { continue; }
      //Note: function.bind is not supported in IE < 9
      this[key] = typeof fn.bind === 'function' ? fn.bind(this) : undefined;
    }
  }
});
Cls.initClass();

function __guard__(value, transform) {
  return (typeof value !== 'undefined' && value !== null) ? transform(value) : undefined;
}
function __guardMethod__(obj, methodName, transform) {
  if (typeof obj !== 'undefined' && obj !== null && typeof obj[methodName] === 'function') {
    return transform(obj, methodName);
  } else {
    return undefined;
  }
}
