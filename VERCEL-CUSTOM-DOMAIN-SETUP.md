# Vercel Custom Domain Setup - analytics.candidstudios.net

## Step 1: Add Domain in Vercel (5 minutes)

1. **Go to Vercel Dashboard:**
   - Visit: https://vercel.com/dashboard
   - Find your project: `candid-analytics` (or similar name)

2. **Add Custom Domain:**
   - Click on the project
   - Go to: **Settings** → **Domains**
   - Click **Add Domain**
   - Enter: `analytics.candidstudios.net`
   - Click **Add**

3. **Get DNS Configuration:**
   - Vercel will show you the DNS records needed
   - You'll see either:
     - **CNAME record** (most common), OR
     - **A records** (IP addresses)

## Step 2: Configure DNS (10 minutes)

### If Vercel Shows CNAME Record:

```
Type: CNAME
Name: analytics (or @)
Value: cname.vercel-dns.com (or similar)
TTL: 3600
```

### If Vercel Shows A Records:

```
Type: A
Name: analytics
Value: 76.76.21.21 (example IP from Vercel)
TTL: 3600
```

## Step 3: Add DNS Records to Your DNS Provider

### Option A: Using Cloudflare (Most Likely)

1. Go to: https://dash.cloudflare.com/
2. Select domain: `candidstudios.net`
3. Click **DNS** → **Records**
4. Click **Add record**
5. Enter the values Vercel provided:
   - **Type:** CNAME (or A)
   - **Name:** analytics
   - **Target:** (value from Vercel)
   - **Proxy status:** DNS only (gray cloud) - IMPORTANT!
   - **TTL:** Auto
6. Click **Save**

### Option B: Using SiteGround (If DNS is there)

1. Go to: https://my.siteground.com/
2. Find: candidstudios.net → DNS Zone Editor
3. Add new record:
   - **Type:** CNAME (or A)
   - **Host:** analytics
   - **Points to:** (value from Vercel)
   - **TTL:** 3600
4. Click **Create**

## Step 4: Verify in Vercel (2 minutes)

1. Back in Vercel → **Settings** → **Domains**
2. Wait 1-5 minutes for DNS propagation
3. Vercel will automatically verify the domain
4. You'll see a green checkmark when ready

## Step 5: SSL Certificate (Automatic)

- Vercel automatically provisions SSL certificate (Let's Encrypt)
- This takes 1-2 minutes after DNS verification
- Your site will be accessible at: `https://analytics.candidstudios.net`

## Step 6: Update Frontend Environment Variable

Once domain is verified, update the API URL if needed:

1. In Vercel dashboard → **Settings** → **Environment Variables**
2. Verify `VITE_API_URL` = `https://api.candidstudios.net`
3. If you made changes, trigger a redeploy

## Expected Timeline

- **DNS Propagation:** 1-10 minutes (usually instant with Cloudflare)
- **SSL Certificate:** 1-2 minutes (automatic)
- **Total Time:** ~15 minutes

## Troubleshooting

### "Domain Not Verified"
- Check DNS records are correct (exact match from Vercel)
- If using Cloudflare, ensure proxy is OFF (gray cloud)
- Wait 5-10 minutes for DNS propagation
- Use DNS checker: https://dnschecker.org/

### "Certificate Error"
- Wait 2-3 minutes for SSL to provision
- Vercel auto-provisions Let's Encrypt certificates
- No action needed on your part

### "Still Shows Vercel Authentication"
- Clear browser cache
- Try incognito/private window
- Custom domains bypass Vercel protection automatically

## After Setup Complete

Your dashboard will be accessible at:
- **Production URL:** https://analytics.candidstudios.net
- **Login:** admin / password
- **API:** https://api.candidstudios.net

✅ Custom domain = No more Vercel authentication screen!
✅ Professional branded URL for your team
✅ SSL certificate included (HTTPS)

## Current Vercel Deployment URL

Temporary URL (will redirect to custom domain once set up):
https://candid-analytics-fky6y2vam-support-6191s-projects.vercel.app/

---

**Next Steps After Domain is Live:**
1. Test login functionality
2. Verify all 8 dashboard pages load
3. Configure Make.com webhooks to use new domain
4. Update team bookmarks/documentation
