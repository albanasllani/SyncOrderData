# Webhook Integration Plugin

This plugin sends order data to a specified webhook when the shipping status of orders changes. It also includes flow action to use in the flow builder and settings to prevent duplicate requests.

## Features

- **Order Data on Shipping Status Change**  
  Sends order data to the webhook whenever the shipping status is updated in the administration panel.

- **Flow Action Support**  
  Provides a flow action in the Flow Builder that allows the same function to be triggered for order status updates.

- **Settings to Prevent Duplicate Requests**  
  Includes settings to ensure only one function is triggered to avoid duplicate requests.

## Webhook URL

The data will be sent to the following webhook URL:  
[https://webhook.site/5138e751-daad-4b28-b540-cd87b51131a1](https://webhook.site/5138e751-daad-4b28-b540-cd87b51131a1)

## Installation

1. Download or clone the repository.
2. Configure the webhook URL in the settings file
3. Run bin/console messenger:consume async -vv on the command line so you can execute queued messages.

## Usage

### Shipping Status Change
When an order's shipping status is updated in the admin panel, the plugin will automatically send the order data to the configured webhook.

### Flow Action
You can also trigger the same action via the Flow Builder.

### Settings Configuration
Add URL where the data will be sent to
To ensure there are no duplicate requests, configure the settings in the plugin to check for the current status before triggering the Flow Action webhook.



