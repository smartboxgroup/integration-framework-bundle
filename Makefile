DOCKER_COMPOSE  	= docker-compose
EXEC        		= $(DOCKER_COMPOSE) exec php
RUN        			= $(DOCKER_COMPOSE) run php
COMPOSER        	= $(RUN) php -d memory_limit=-1 /usr/bin/composer

build:
	@$(DOCKER_COMPOSE) pull --parallel --quiet --ignore-pull-failures 2> /dev/null
	$(DOCKER_COMPOSE) build --pull

kill:
	$(DOCKER_COMPOSE) kill
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

start: up test ## Start the project

up: rights ## Up the project
	$(DOCKER_COMPOSE) up -d --build --remove-orphans --no-recreate

stop: ## Stop the project
	$(DOCKER_COMPOSE) stop

composer-install: ## Execute composer instalation
	$(COMPOSER) install --prefer-dist

test: composer-install ## Execute composer instalation
#	$(RUN) bin/simple-phpunit --filter 'DbalStepsProviderTest::testDoctrineGetConnection' Tests/Unit/Components/DB/Dbal/DbalStepsProviderTest.php
	$(RUN) bin/simple-phpunit
#	$(RUN) bin/simple-phpunit --filter 'AsyncQueueConsumerTest::testConsume' Tests/Functional/Consumers/AsyncQueueConsumerTest.php
#	$(RUN) bin/simple-phpunit --filter 'AbstractAsyncConsumerTest::testCallbackMeasuresProcessingDuration' Tests/Unit/Core/Consumers/AbstractAsyncConsumerTest.php
#	$(RUN) bin/simple-phpunit --filter 'RecipientListTest::testItShouldDispatchAnEventForEveryRecipientOnProcess' Tests/Unit/Core/Processors/Routing/RecipientListTest.php

tree:
	$(COMPOSER) depends --tree jms/serializer
php:
	$(EXEC) .Tests/App/console --version

