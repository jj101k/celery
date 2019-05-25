# Rationale

A typical lightweight PHP service takes 70ms loading the PHP includes
it requires when a request starts, then less than 1ms handling
trivial requests or 7ms handling a request that requires database
usage. In 70ms, an average CPU has had time to execute about
140,000,000 instructions, far more than a web service could reasonably
use!

That's silly.

So instead of wasting all that time - not to mention the carbon needed for the
energy - starting up, this helps you reduce it by keeping the HTTP framework
part of that startup cost down.

# Compatibility

The following should be compatible with typical use-cases in Slim:

- any()
- delete()
- get()
- group()
- map()
- post()
- put()
- run()
- Error condition handlers (errorHandler, phpErrorHandler, notFoundHandler,
  notAllowedHandler) as added via plain array passed to the constructor
