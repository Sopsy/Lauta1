local reverse, forward
local ip = ngx.var.remote_addr

local function has_regex_match (table, value)
    if (value == nil or value == '') then
        return false
    end

    for i, table_value in ipairs(table) do
        if string.match(value, table_value) then
            return true
        end
    end

    return false
end

local function has_match (table, value)
    if (value == nil or value == '') then
        return false
    end

    for i, table_value in ipairs(table) do
        if table_value == value then
            return true
        end
    end

    return false
end

-- Allow .well-known
if string.match(ngx.var.request_uri, "^/%.well%-known/") then
    return
end

-- Check User-Agent
local user_agent = ngx.req.get_headers()["User-Agent"]

if (type(user_agent) == "table") then
    user_agent = user_agent[1]
end

local google_user_agents_regex = {
    "^Mozilla/5%.0 AppleWebKit/537%.36 %(KHTML, like Gecko; compatible; Googlebot/2%.1; %+http://www%.google%.com/bot%.html%) Chrome/[0-9]+%.[0-9]+%.[0-9]+%.[0-9]+ Safari/537%.36$",
    "^Mozilla/5%.0 %(Linux; Android 6%.0%.1; Nexus 5X Build/MMB29P%) AppleWebKit/537%.36 %(KHTML, like Gecko%) Chrome/[0-9]+%.[0-9]+%.[0-9]+%.[0-9]+ Mobile Safari/537%.36 %(compatible; Googlebot/2%.1; %+http://www%.google%.com/bot%.html%)$",
    " %(compatible; Mediapartners%-Google/2%.1; %+http://www%.google%.com/bot%.html%)$",
    "Chrome%-Lighthouse$",
}

local google_user_agents = {
    "APIs-Google (+https://developers.google.com/webmasters/APIs-Google.html)",
    "Mediapartners-Google",
    "Mozilla/5.0 (Linux; Android 5.0; SM-G920A) AppleWebKit (KHTML, like Gecko) Chrome Mobile Safari (compatible; AdsBot-Google-Mobile; +http://www.google.com/mobile/adsbot.html)",
    "Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1 (compatible; AdsBot-Google-Mobile; +http://www.google.com/mobile/adsbot.html)",
    "AdsBot-Google (+http://www.google.com/adsbot.html)",
    "Googlebot-Image/1.0",
    "Googlebot-News",
    "Googlebot-Video/1.0",
    "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
    "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Googlebot/2.1; +http://www.google.com/bot.html) Safari/537.36",
    "Googlebot/2.1 (+http://www.google.com/bot.html)",
    "AdsBot-Google-Mobile-Apps",
    "FeedFetcher-Google; (+http://www.google.com/feedfetcher.html)",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.118 Safari/537.36 (compatible; Google-Read-Aloud; +https://support.google.com/webmasters/answer/1061943)",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.75 Safari/537.36 Google Favicon",
    "Mozilla/5.0 (Linux; Android 8.0; Pixel 2 Build/OPD3.170816.012; DuplexWeb-Google/1.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Mobile Safari/537.36",
}

-- Not a googlebot
if (not has_match(google_user_agents, user_agent)) and (not has_regex_match(google_user_agents_regex, user_agent)) then

    -- Block HTTP/1.x connections
    if (ngx.var.server_protocol ~= 'HTTP/2.0' and ngx.var.server_port == '443') then
        ngx.exit(ngx.HTTP_CLOSE)
    end

    return
end

-- Allow Chrome-Lighthouse HTTP/2
if (string.match(user_agent, " Chrome%-Lighthouse$") and ngx.var.server_protocol == 'HTTP/2.0') then
    return
end

local resolver = require "nginx.dns.resolver"
local r, err = resolver:new{
    nameservers = {"127.0.0.53"},
    retrans = 3,
    timeout = 1000,
}

-- Resolver initialization failed
if not r then
    ngx.log(ngx.STDERR, 'Resolver initialization failed: ', err)
    return
end

-- Reverse DNS first
local answers, err = r:reverse_query(ip)
if err then
    ngx.log(ngx.STDERR, 'Reverse query failed: ', err)
    return
end

if not answers or answers.errcode then
    ngx.log(ngx.STDERR, 'FAKE GOOGLEBOT: Empty reverse')
    ngx.exit(ngx.HTTP_CLOSE)
end

for i, ans in ipairs(answers) do
    if not ans.ptrdname then
        ngx.exit(ngx.HTTP_CLOSE)
    end

    -- Only check the first response. There should never be more.
    reverse = ans.ptrdname;
    break
end

-- Something failed in the DNS query
if not reverse then
    ngx.log(ngx.STDERR, 'Reverse query failed: empty reverse')
    return
end

-- Verify reverse is from google
if not string.match(reverse, "%.googlebot%.com$") and not string.match(reverse, "%.google%.com$") then
    ngx.log(ngx.STDERR, 'FAKE GOOGLEBOT: Not a googlebot-reverse')
    ngx.exit(ngx.HTTP_CLOSE)
end

-- Validate reverse DNS
local answers, err = r:query(reverse)
if not answers or answers.errcode then
    return
end

for i, ans in ipairs(answers) do
    if not ans.address then
        ngx.log(ngx.STDERR, 'FAKE GOOGLEBOT: Empty ans.address')
        ngx.exit(ngx.HTTP_CLOSE)
    end

    -- Only check the first response. There should never be more.
    forward = ans.address;
    break
end

-- Something failed in the DNS query
if not forward then
    ngx.log(ngx.STDERR, 'Forward query failed: empty forward')
    return
end

-- Verify IP matches forward DNS
if ip ~= forward then
    ngx.log(ngx.STDERR, 'FAKE GOOGLEBOT: IP does not match forward (', forward, '/', reverse ,')')
    ngx.exit(ngx.HTTP_CLOSE)
end

ngx.var.is_allowed_bot = '1';
