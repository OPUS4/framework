node {
    stage "checkout"
    checkout scm
    stage "docker"

    docker.image('mysql:5').withRun('-e "MYSQL_ROOT_PASSWORD=root" -p 3388:3306') { c ->

        stage "wait"
        sh 'while ! mysqladmin ping -h0.0.0.0 --silent; do sleep 1; done'

        stage "build"
        sh 'ant setup build-fast'

        stage "test"
        sh 'ant phpunit-fast'
    }
}
