# HootDraft: PHP Rewrite

Given that HootDraft's underlying dependencies have been outdated for some time, combined with the fact that software vulnerabilities are being discovered and patched at a rate we have never seen thanks to LLMs - I've decided it's time to try and tackle the bitrot that has naturally set in here.

## Overarching Issues

A rudimentary look over the API code, while not revealing perfection, did reveal that most of the issues aren't necessarily in the code (though I will fix those). The issue is that packages/frameworks like Silex have been deprecated and untouched for many years now.

### Attack Plan

1. Migrate the application internals & bootstrapping (now in `/old-api`, mostly in the `/config` folder) from Silex 2 to Symfony 8.1 micro-kernel (requiring PHP >= 8.4)
2. Shore up user authentication
  a. Rely on Symfony built-ins as much as possible
  b. Move from JWT to just cookie based auth, which removes an open XSS attack surface on the client.
  c. Make the smallest changes on the AngularJS side, as I will approach an Angular rewrite later.
3. Begin porting code over, controller at a time
