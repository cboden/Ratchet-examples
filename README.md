### Installation

If you'll be running the website IRC chat demo make sure to run composer with dev mode:

    composer.phar install --dev

### bin/

Run scripts in here to launch you socket server.

### src/

PSR classes.  These are the logic of applications. The Tutorial folder contains example applications found on http://socketo.me and the Cookbook has useful middleware classes (such as a logger).

### webpages/

HTML/Javascript pages to interact with a running Ratchet server using the WebSocketComponent. (coming soon)

---

## Vendor Support

Ratchet Examples comes with a lot of composer dependencies.  Here is a list of what each one's purpose is:

### AutobahnJS

AutobahnJS is the JavaScript library to be used in conjunction with the WAMPServerComponent. It provides an Pub/Sub and RPC sub-protocol on top of WebSockets. 

### When

When is a JavaScript library required by AutobahnJS for deferred calls.

### web-socket-js

A Flash polyfill for WebSockets.  Drop this in and if WebSockets aren't supported by the user's browser this library uses Flash to make the browser talk with your Ratchet app. 
Make sure, if using this library, to run a second Ratchet application using the FlashPolicyComponent.  Flash Sockets, before communicating in WebSockets, need pre-authorization. 

### Monolog

Monolog is an feature rich, abstract logging library for PHP. It is used in one of the Cookbook source code examples.

### Guzzle

Guzzle is a PHP library meant for abstracting cURL calls and working with RESTful APIs...Ratchet requires Guzzle to act as its HTTP server!

### Ratchet

Build your PHP WebSocket library!