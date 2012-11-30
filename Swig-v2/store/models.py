from django.db import models
from django.contrib.auth.models import User
from django.utils.translation import ugettext_lazy as _

class UserProfile(models.Model):
	user = models.OneToOneField(User)	

class OrderItem(models.Model):
	ORDER_ITEM_TYPE_CHOICES = (
		('B', 'Beer'),
		('W', 'Wine'),
		('L', 'Liquor'),
		('O', 'Other'),
	)
	item_type = models.CharField(max_length=2, choices=ORDER_ITEM_TYPE_CHOICES)
	price = models.DecimalField(max_digits=7, decimal_places=2)
	name = models.CharField(max_length=70)
	image = models.URLField()
	def __unicode__(self):
		return self.name

class OrderLine(models.Model):
	order = models.ForeignKey('Order')
	order_item = models.ForeignKey('OrderItem')
	quantity = models.IntegerField(default=1)
	other_fees = models.DecimalField(max_digits=7, decimal_places=2)

class Order(models.Model):
	user = models.ForeignKey(User)
	

class Address(models.Model):
	user = models.ForeignKey(User)
	TYPES_CHOICES = (
		('HOME', 'Home'),
		('WORK', 'Work'),
		('OTHER','Other')
	)
	type = models.CharField(_('Type'), max_length=20, choices = TYPES_CHOICES)	
	firstname = models.CharField(_('Firstname'), max_length = 50, blank = True)
	lastname = models.CharField(_('Lastname'), max_length = 50, blank = True)
	departement = models.CharField(_('Departement'), max_length = 50, blank = True)
	corporation = models.CharField(_('Corporation'), max_length = 100, blank = True)
	building = models.CharField(_('Building'), max_length = 20, blank = True)
	floor = models.CharField(_('Floor'), max_length = 20, blank = True)
	door = models.CharField(_('Door'), max_length = 20, blank = True)
	number = models.CharField(_('Number'), max_length = 30, blank = True)
	street_line1 = models.CharField(_('Address 1'), max_length = 100, blank = True)
	street_line2 = models.CharField(_('Address 2'), max_length = 100, blank = True)
	zipcode = models.CharField(_('ZIP code'), max_length = 5, blank = True)
	city = models.CharField(_('City'), max_length = 100, blank = True)
	state = models.CharField(_('State'), max_length = 100, blank = True)	
