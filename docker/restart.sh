#!/usr/bin/env bash

APP_PID=`supervisorctl pid boom:boom_00`

echo "Killing app $APP_PID";

kill -15 $APP_PID;