#!/bin/bash

set -eu

REPO=rschick
SERVICE=lti-quiz-sample
VERSION=1.0.0

TAG=$REPO/$SERVICE-$VERSION

docker build -t $TAG .
docker tag -f $TAG $REPO/$SERVICE:latest

# Deploy image to Docker Hub
docker push $REPO/$SERVICE
