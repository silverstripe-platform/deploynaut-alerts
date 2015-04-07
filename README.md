# Deploynaut Alerts

# Pingdom configuration

`mysites/_config/pindom.yaml

	---
	Name: my_pingdom
	After:
	  - '#pingdom'
	---
	Injector:
      PingdomService:
        class: "\Acquia\Pingdom\PingdomApi"
        constructor:
          0: 'user@domain.com'
          1: 'password'
          2: 'token'


