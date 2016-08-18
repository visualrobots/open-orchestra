# This is a basic Open Orchestra VCL configuration file for varnish 4.

vcl 4.0;
import directors;

acl purgers {
  "oo_apache_php";
}

acl invalidators {
  "oo_apache_php";
}

backend f1 {
    .host = "oo_apache_php";
    .port = "80";
}
backend g1 {
    .host = "oo_apache_php";
    .port = "80";
}

sub vcl_init {
    new back = directors.round_robin();
    back.add_backend(g1);

    new front = directors.round_robin();
    front.add_backend(f1);
}

sub vcl_recv {
    set req.backend_hint = front.backend();

    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ invalidators) {
        set req.hash_always_miss = true;
    }

    #=== Pass when request for admin ===#
    if(req.http.host ~ "(admin.openorchestra.1-2.dev)") {
        set req.backend_hint = back.backend();

        return (pass);
     }

    #=== Pass when request for preview ===#
    if (req.url ~ "^/preview") {
        return (pass);
    }

    #=== Add X-Forwarded-Port ===#
    if (req.http.X-Forwarded-Proto == "https" ) {
        set req.http.X-Forwarded-Port = "443";
    } else {
        set req.http.X-Forwarded-Port = "6081";
    }

    #=== Add Surrogate-Capability ===#
    set req.http.Surrogate-Capability = "varnish=ESI/1.0";

    #=== BAN request ===#
    if (req.method == "BAN") {
        if (!client.ip ~ invalidators) {
            return (synth(405, "Ban not allowed"));
        }

        if (req.http.X-Cache-Tags) {
            ban("obj.http.X-Host ~ " + req.http.X-Host
                + " && obj.http.X-Url ~ " + req.http.X-Url
                + " && obj.http.content-type ~ " + req.http.X-Content-Type
                + " && obj.http.X-Cache-Tags ~ " + req.http.X-Cache-Tags
            );
        } else {
            ban("obj.http.X-Host ~ " + req.http.X-Host
                + " && obj.http.X-Url ~ " + req.http.X-Url
                + " && obj.http.content-type ~ " + req.http.X-Content-Type
            );
        }

        return (synth(200, "Ban added"));
    }

    #=== PURGE request ===#
    if (req.method == "PURGE") {
        if (!client.ip ~ purgers) {
             return (synth(405, "Purge not allowed"));
        }
        return(purge);
    }

    #=== Normalize Accept-Encoding header ===#
    if (req.http.Accept-Encoding) {
        if (req.url ~ "\.(jpg|png|gif|gz|tgz|bz2|tbz|mp3|ogg)$") {
            unset req.http.Accept-Encoding;
        } elsif (req.http.Accept-Encoding ~ "gzip") {
            set req.http.Accept-Encoding = "gzip";
        } elsif (req.http.Accept-Encoding ~ "deflate") {
            set req.http.Accept-Encoding = "deflate";
        } else {
            unset req.http.Accept-Encoding;
        }
    }

    #=== Pass when method different from GET and HEAD ===#
    if (req.method != "GET" && req.method != "HEAD") {
        return (pass);
    }


    #=== Remove all cookies ===#
    unset req.http.Cookie;

    #=== If you want to keep the session ID ===#
    #=== Comment the previous line          ===#
    #=== And uncomment the following block  ===#
#    if (req.http.Cookie) {
#        set req.http.Cookie = ";" + req.http.Cookie;
#        set req.http.Cookie = regsuball(req.http.Cookie, "; +", ";");
#        set req.http.Cookie = regsuball(req.http.Cookie, ";(PHPSESSID)=", "; \1=");
#        set req.http.Cookie = regsuball(req.http.Cookie, ";[^ ][^;]*", "");
#        set req.http.Cookie = regsuball(req.http.Cookie, "^[; ]+|[; ]+$", "");
#
#        if (req.http.Cookie == "") {
#            unset req.http.Cookie;
#        }
#    }

    return (hash);
}

sub vcl_pipe {
    # By default Connection: close is set on all piped requests, to stop
    # connection reuse from sending future requests directly to the
    # (potentially) wrong backend. If you do want this to happen, you can undo
    # it here.
    # unset bereq.http.connection;
    return (pipe);
}

sub vcl_pass {
    return (fetch);
}

sub vcl_hash {
    hash_data(req.url);

    if (req.http.host) {
        hash_data(req.http.host);
    } else {
        hash_data(server.ip);
    }

    if (req.http.X-UA-Device) {
        hash_data(req.http.X-UA-Device);
    }

    return (lookup);
}

sub vcl_purge {
    return (synth(200, "Purged"));
}

sub vcl_hit {
    if (obj.ttl >= 0s) {
        // A pure unadultered hit, deliver it
        return (deliver);
    }

    if (obj.ttl + obj.grace > 0s) {
        // Object is in grace, deliver it
        // Automatically triggers a background fetch
        return (deliver);
    }

    // fetch & deliver once we get the result
    return (fetch);
}

sub vcl_miss {
    return (fetch);
}

sub vcl_deliver {
    # Keep ban-lurker headers only if debugging is enabled
    if (!resp.http.X-Cache-Debug) {
        # Remove ban-lurker friendly custom headers when delivering to client
        unset resp.http.X-Url;
        unset resp.http.X-Host;
        unset resp.http.X-Cache-Tags;
    }

    return (deliver);
}

/*
 * We can come here "invisibly" with the following errors: 413, 417 & 503
 */
sub vcl_synth {
    set resp.http.Content-Type = "text/html; charset=utf-8";
    set resp.http.Retry-After = "5";
    synthetic( {"<!DOCTYPE html>
<html>
  <head>
    <title>"} + resp.status + " " + resp.reason + {"</title>
  </head>
  <body>
    <h1>Error "} + resp.status + " " + resp.reason + {"</h1>
    <p>"} + resp.reason + {"</p>
    <h3>Guru Meditation:</h3>
    <p>XID: "} + req.xid + {"</p>
    <hr>
    <p>Varnish cache server</p>
  </body>
</html>
"} );

    return (deliver);
}

#######################################################################
# Backend Fetch

sub vcl_backend_fetch {
    return (fetch);
}

sub vcl_backend_response {
    # Set ban-lurker friendly custom headers
    set beresp.http.X-Url = bereq.url;
    set beresp.http.X-Host = bereq.http.host;

    if (beresp.status == 404 || beresp.status == 500 || beresp.status == 503) {
        set beresp.ttl = 30s;
    }

    if (beresp.ttl <= 0s ||
      beresp.http.Surrogate-control ~ "no-store" ||
      (!beresp.http.Surrogate-Control &&
        beresp.http.Cache-Control ~ "no-cache|no-store|private") ||
      beresp.http.Vary == "*") {
        /*
        * Mark as "Hit-For-Pass" for the next 2 minutes
        */
        set beresp.ttl = 120s;
        set beresp.uncacheable = true;
    }

    if (beresp.http.Surrogate-Control ~ "ESI/1.0") {
        unset beresp.http.Surrogate-Control;
        set beresp.do_esi = true;
    }

    if (beresp.ttl>0s && !beresp.uncacheable) {
      unset beresp.http.Set-Cookie;
    }

    return (deliver);
}

sub vcl_backend_error {
    set beresp.http.Content-Type = "text/html; charset=utf-8";
    set beresp.http.Retry-After = "5";
    synthetic( {"<!DOCTYPE html>
<html>
  <head>
    <title>"} + beresp.status + " " + beresp.reason + {"</title>
  </head>
  <body>
    <h1>Error "} + beresp.status + " " + beresp.reason + {"</h1>
    <p>"} + beresp.reason + {"</p>
    <h3>Guru Meditation:</h3>
    <p>XID: "} + bereq.xid + {"</p>
    <hr>
    <p>Varnish cache server</p>
  </body>
</html>
"} );

    return (deliver);
}

#######################################################################
# Housekeeping

sub vcl_fini {
    return (ok);
}
