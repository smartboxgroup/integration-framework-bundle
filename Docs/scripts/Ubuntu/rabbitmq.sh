# This will:
# Setup RabbitMQ on Ubuntu
# Add an administrator called "mel" with the password "mel".
# Allow remote access to the management console.
# And finally it will add rabbitmq.local to /etc/hosts

sudo apt-get install rabbitmq-server
rabbitmq-plugins enable rabbitmq_management
rabbitmq-plugins enable rabbitmq_stomp
rabbitmqctl add_user mel mel
rabbitmqctl set_user_tags mel administrator
rabbitmqctl set_permissions -p / mel "." "." ".*"
sudo touch /etc/rabbitmq/rabbitmq.config
echo "[{rabbit, [{loopback_users, []}]}]." | sudo tee /etc/rabbitmq/rabbitmq.config

echo -e "\n127.0.0.1	rabbitmq.local" | sudo tee -a /etc/hosts