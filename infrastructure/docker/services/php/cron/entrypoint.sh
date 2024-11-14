#!/usr/bin/env bash

if ! id -u ${USER_ID} &>/dev/null; then
    useradd -u ${USER_ID} -o -m -s /bin/bash ${USER_ID}
fi

exec "$@"
