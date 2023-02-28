#!/bin/bash
DIR_HYP2000=/app/hyp2000
DIR_PYML=/app/pyml
FILE_DOCKER_PID=/var/run/docker.pid
BASENAME=$( basename $0 )

echo "START - ${BASENAME} -> apolloBuildDindImages.sh"
#
if [ ! -f $(which docker) ]; then
    echo " docker doesn't exist!"
    exit 1
fi

#
COUNT=1
COUNT_LIMIT=50
#while [ ! -f /var/run/docker.pid ] && (( ${COUNT} < ${COUNT_LIMIT} )); do
while [[ $(docker -v 2>&1 >/dev/null) ]] && (( ${COUNT} < ${COUNT_LIMIT} )); do
    echo " ${COUNT}/${COUNT_LIMIT} - waiting docker-in-docker starts." 
    COUNT=$(( ${COUNT} + 1))
    sleep 1
done

if docker -v 2>&1 >/dev/null ; then
    # START - hyp2000
    echo "Pull/Build hyp2000:"
    if docker image ls | grep -q "ingv/hyp2000" ; then 
        echo " nothing to do" 
    else 
        echo " try to pull: \"ingv/hyp2000:ewdevgit\""
        RET=$(docker pull ingv/hyp2000:ewdevgit 2>/dev/null >/dev/null && echo y || echo n)
        if [[ "${RET}" == "n" ]]; then
            echo "  pull error! try to build locally"
            if [ -d ${DIR_HYP2000} ]; then
                cd ${DIR_HYP2000}
                docker build --tag ingv/hyp2000:ewdevgit -f DockerfileEwDevGit .
            fi
        fi
        # Final check
        if ! docker image ls | grep -q "ingv/hyp2000" ; then
            echo " docker image \"ingv/hyp2000\" doesn't exist."
            exit 1
        fi
    fi
    echo "done"
    echo ""
    # END - hyp2000

    # START - pyml
    echo "Pull/Build pyml:"
    if docker image ls | grep -q pyml ; then 
        echo " nothing to do" 
    else
        if [ -d ${DIR_PYML} ]; then
            cd ${DIR_PYML}
            docker build --tag pyml .
        fi
    fi
    echo "done"
    echo ""
    # END - pyml
else
    echo " ERROR: docker-in-docker not started!"
    echo "END - ${BASENAME}"
    exit 1
fi

cd /app

echo "END - ${BASENAME} -> apolloBuildDindImages.sh"
echo ""
