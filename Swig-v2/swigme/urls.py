from django.conf.urls import patterns, include, url

# Uncomment the next two lines to enable the admin:
from django.contrib import admin
admin.autodiscover()

urlpatterns = patterns('',
	url(r'^$', 'store.views.show_store', name='Swigme'),
	
	#bogus login form
	#url(r'^$', 'store.views.login_user', name='Login'),


    # Login / logout.

    # Examples:
    # url(r'^$', 'swigme.views.home', name='home'),
    # url(r'^swigme/', include('swigme.foo.urls')),

    # Uncomment the admin/doc line below to enable admin documentation:
    url(r'^admin/doc/', include('django.contrib.admindocs.urls')),

    # Uncomment the next line to enable the admin:
    url(r'^admin/', include(admin.site.urls)),
)
