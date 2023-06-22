# -*- coding: utf-8 -*-
from fabric.api import cd, env, require, run, task
from fabric.colors import green, white
from fabric.utils import puts

from fabutils.context import cmd_msg
from fabutils.env import set_env_from_json_file
from fabutils.tasks import ulocal, urun, ursync_project
from fabutils.text import SUCCESS_ART


@task
def environment(env_name):
    """
    Creates a dynamic environment based on the contents of the given
    environments_file.
    """
    if env_name == 'vagrant':
        result = ulocal('vagrant ssh-config | grep IdentityFile', capture=True)
        env.key_filename = result.split()[1].replace('"', '')

    set_env_from_json_file('environments.json', env_name)


@task
def bootstrap():
    """
    Builds the environment to start the project in local
    """
    migrate()
    dbseed()


@task
def dbseed(*args):
    """
    Loads the given data seeds into the project's database.
    """
    with cd(env.site_dir):
        run('php artisan db:seed --env={0}'.format(env.environment))


@task
def makemigrations(*args, **kwargs):
    """
    Creates the new migrations.
    """
    with cd(env.site_dir):
        name = raw_input(u'Migration name: ')
        run('php artisan migrate:make %s' % name)


@task
def migrate():
    """
    Syncs the DB and applies the available migrations.
    """
    with cd(env.site_dir):
        run('php artisan migrate --env={0}'.format(env.environment))


@task
def deploy(git_ref, upgrade=False):
    """
    Deploy the code of the given git reference to the previously selected
    environment.
    Pass ``upgrade=True`` to upgrade the versions of the already installed
    project requirements (with pip).
    """
    require('hosts', 'user', 'group', 'site_dir', 'environment')

    # Retrives git reference metadata and creates a temp directory with the
    # contents resulting of applying a ``git archive`` command.
    message = white('Creating git archive from {0}'.format(git_ref), bold=True)
    with cmd_msg(message):
        repo = ulocal(
            'basename `git rev-parse --show-toplevel`', capture=True)
        commit = ulocal(
            'git rev-parse --short {0}'.format(git_ref), capture=True)

        tmp_dir = '/tmp/blob-{0}-{1}/'.format(repo, commit)

        ulocal('rm -fr {0}'.format(tmp_dir))
        ulocal('mkdir {0}'.format(tmp_dir))
        ulocal('git archive {0} ./src | tar -xC {1} --strip 1'.format(
            commit, tmp_dir))

    # Uploads the code of the temp directory to the host with rsync telling
    # that it must delete old files in the server, upload deltas by checking
    # file checksums recursivelly in a zipped way; changing the file
    # permissions to allow read, write and execution to the owner, read and
    # execution to the group and no permissions for any other user.
    with cmd_msg(white('Uploading code to server...', bold=True)):
        ursync_project(
            local_dir=tmp_dir,
            remote_dir=env.site_dir,
            delete=True,
            default_opts='-chrtvzP',
            extra_opts='--chmod=750',
            exclude=["*.lock", "*.env", "storage/", "vendor/", "node_modules/", "dev_node_modules/"]
        )

    # Performs the deployment task, i.e. Install/upgrade project
    # requirements, syncronize and migrate the database changes, collect
    # static files, reload the webserver, etc.
    message = white('Running deployment tasks', bold=True)
    with cmd_msg(message, grouped=True):
        with cd(env.site_dir):
            """
            if upgrade:
                message = white('Update packages with composer')
                with cmd_msg(message, spaces=2):
                    run('composer update')
            else:
                message = white('Installing packages with composer')
                with cmd_msg(message, spaces=2):
                    run('composer install')"""

            # message = white('Maintenance mode ON')
            # with cmd_msg(message, spaces=2):
            #    run('php artisan down')

            # message = white('Migrating database')
            # with cmd_msg(message, spaces=2):
            #    run('php artisan migrate --env={0}'.format(env.environment))

            message = white('Setting file permissions')
            with cmd_msg(message, spaces=2):
                run('chgrp -R {0} .'.format(env.group))

            # message = white('Maintenance mode OFF')
            # with cmd_msg(message, spaces=2):
            #    run('php artisan up')

            message = white('Clear cache and config')
            with cmd_msg(message, spaces=2):
                run('php artisan cache:clear')
                run('php artisan config:cache')
                run('php artisan config:clear')


    # Clean the temporary snapshot files that was just deployed to the host
    message = white('Cleaning up...', bold=True)
    with cmd_msg(message):
        ulocal('rm -fr {0}'.format(tmp_dir))

    puts(green(SUCCESS_ART), show_prefix=False)
    puts(white('Code from {0} was succesfully deployed to host {1}'.format(
        git_ref, ', '.join(env.hosts)), bold=True), show_prefix=False)
