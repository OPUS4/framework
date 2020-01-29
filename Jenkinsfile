def jobNameParts = JOB_NAME.tokenize('/') as String[]
def projectName = jobNameParts[0]
def buildType = "short"

if (projectName.contains('night')) {
    buildType = "long"
}

pipeline {
    agent { dockerfile {args "-u root -v /var/run/docker.sock:/var/run/docker.sock"}}

    triggers {
        cron( buildType.equals('long') ? 'H 3 * * *' : '')
    }

    stages {
        stage('Composer') {
            steps {
                sh 'composer install'
            }
        }

        stage('MySQL') {
            steps {
                sh 'sudo apt-get update'
                sh 'sudo bash bin/install_mysql_docker.sh'
            }
        }

        stage('Prepare Opus4') {
            steps {
                sh 'ant prepare-workspace prepare-config lint -DdbUserPassword=root -DdbAdminPassword=root'
                sh 'pecl install xdebug-2.8.0 && echo "zend_extension=/usr/lib/php/20151012/xdebug.so" >> /etc/php/7.0/cli/php.ini'
                sh 'sudo useradd opus4 && chown -R opus4:opus4 .'
            }
        }

        stage('Test') {
            steps {
                script{
                    switch (buildType) {
                        case "long":
                            sh 'sudo -E -u opus4 ant phpunit'
                            break
                        default:
                            sh 'sudo -E -u opus4 ant phpunit-fast'
                            break
                  }
                }
            }
        }

        stage('Analyse') {
            steps {
                script{
                   switch (buildType) {
                       case "long":
                           sh 'ant analyse-code'
                           breaek
                       default:
                            break
                   }
                }
            }
        }
    }

    post {
        always {
            step([
                $class: 'JUnitResultArchiver',
                testResults: 'build/logs/phpunit.xml'
            ])
            step([
                $class: 'CloverPublisher',
                cloverReportDir: 'build/coverage',
                cloverReportFileName: 'phpunit.coverage.xml"'
            ])
            step([
                $class: 'hudson.plugins.checkstyle.CheckStylePublisher',
                pattern: 'build/logs/checkstyle.xml'
            ])
            step([
                $class: 'hudson.plugins.dry.DryPublisher',
                pattern: 'build/logs/pmd-cpd.xml'
            ])
            step([
                $class: 'hudson.plugins.pmd.PmdPublisher',
                pattern: 'build/logs/pmd.xml'
            ])
            sh "chmod -R 777 ."
            step([$class: 'WsCleanup', externalDelete: 'rm -rf *'])
        }
    }
}
