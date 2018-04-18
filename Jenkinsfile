node {
    stage "checkout"
    checkout scm
    stage "prepare"

    docker.image('mysql:5').withRun('-e "MYSQL_ROOT_PASSWORD=root" -p 3388:3306') { c ->
        sh 'while ! mysqladmin ping -h0.0.0.0 --silent; do sleep 1; done'

        stage "build"
        sh 'ant setup prepare lint'

        stage "test"
        sh 'ant analyse-code'

        stage "Post-Script"
            step([$class: 'hudson.plugins.checkstyle.CheckStylePublisher', checkstyle: '${basedir}/build/logs/checkstyle.xml'])
            step([$class: 'hudson.plugins.dry.DryPublisher', CopyPasteDetector: '${basedir}/build/logs/phpcpd.xml'])
    }
}
