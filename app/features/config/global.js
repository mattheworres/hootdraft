// We want to abstract away "extend", because it's conflated with _.extend and Coffee's "extends"
//const root = this;
const root = window;
root.def = root.extend;
