# Transporter

Photo album adding app that posts to a server using the ActivityPub client-to-server protocol, ish, with custom ActivityStreams 2.0 extension vocab. Transporter assumes photos are already uploaded somewhere and served as a simple one-page `as:Collection` with `as:items`, and just lets you choose a selection of them to add to an album in a batch along with notes and tags.

## Run

```
docker run --rm --interactive --tty \
  --volume $PWD:/app \
  --volume ${COMPOSER_HOME:-$HOME/.composer}:/tmp \
  composer install
```

## Vocab

Posts objects with type `as:Activity` and `as:Add` with `as:object` for the `as:items` in the collection and `as:target` for the collection URI.

## Todo

* [ ] Write TODO list
