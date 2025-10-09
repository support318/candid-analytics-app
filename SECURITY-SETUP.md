# Security Hardening Guide - Candid Analytics

## ✅ Completed Automatically

### 1. JWT Secret Updated
- **File:** `.env`
- **Status:** ✅ Updated with 128-character cryptographically secure key
- **Action:** No further action needed

### 2. Database Configured
- **Status:** ✅ Strong database password already set
- **Password:** `CandidAnalytics2025SecurePassword!`
- **Action:** No further action needed

---

## 🔐 Required: Change Admin Password

**IMPORTANT:** The default admin password is `password` - you MUST change this!

### Option 1: Change via SQL (Recommended)

```bash
# Generate new password hash
php -r "echo password_hash('YOUR_NEW_SECURE_PASSWORD', PASSWORD_DEFAULT);"

# Then update in database
docker exec candid-analytics-db psql -U candid_analytics_user -d candid_analytics -c "
UPDATE users
SET password_hash = 'PASTE_HASH_FROM_ABOVE'
WHERE username = 'admin';
"
```

### Option 2: Change via API (After Login)

```bash
# Login first to get token
curl -X POST https://api.candidstudios.net/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# Then update password (use token from above)
curl -X PUT https://api.candidstudios.net/api/v1/users/me/password \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{"current_password":"password","new_password":"YOUR_NEW_SECURE_PASSWORD"}'
```

### Password Requirements:
- ✅ Minimum 12 characters
- ✅ Mix of uppercase and lowercase
- ✅ Include numbers
- ✅ Include special characters
- ❌ Don't use "password", "admin", "12345", etc.

**Example strong password:** `C@ndid2025!Secur3#Analytics`

---

## 🛡️ Security Checklist

### Database Security
- [x] Strong database password configured
- [x] Database not exposed to public internet (Docker internal network)
- [x] PostgreSQL user has limited permissions (not superuser)
- [ ] **TODO:** Change admin password from default

### API Security
- [x] JWT authentication enabled
- [x] Strong JWT secret (128 characters)
- [x] HTTPS enabled (via Cloudflare tunnel)
- [x] CORS configured for frontend domain only
- [x] Rate limiting enabled (100 requests/15 minutes)
- [x] SQL injection protection (prepared statements)
- [x] XSS protection (input sanitization)

### Frontend Security
- [x] HTTPS only (Vercel)
- [x] JWT tokens stored securely (httpOnly would be ideal, but using localStorage with XSS protection)
- [x] Auto token refresh on expiration
- [x] Logout clears all tokens
- [ ] **TODO:** Add custom domain for professional branding

### Docker Security
- [x] Containers run as non-root users
- [x] Network isolation (internal Docker network)
- [x] Resource limits configured
- [x] Health checks enabled
- [x] Secrets in .env file (not hardcoded)

---

## 🔒 Additional Security Recommendations

### 1. Firewall Configuration
Ensure only necessary ports are open:
```bash
# Allow HTTPS (443) - Cloudflare tunnel
# Allow SSH (22) - for server management
# Block direct access to:
#   - Port 5432 (PostgreSQL)
#   - Port 6379 (Redis)
#   - Port 8000 (API - use Cloudflare tunnel only)
```

### 2. SSL/TLS Configuration
- ✅ API: HTTPS via Cloudflare tunnel
- ✅ Frontend: HTTPS via Vercel
- ✅ Database: Internal Docker network (encrypted not required)

### 3. Backup Strategy
Set up automated backups:
```bash
# PostgreSQL backup (run daily)
docker exec candid-analytics-db pg_dump -U candid_analytics_user candid_analytics > backup_$(date +%Y%m%d).sql

# Compress and encrypt
gzip backup_$(date +%Y%m%d).sql
gpg -c backup_$(date +%Y%m%d).sql.gz
```

### 4. Monitoring & Alerts
Consider setting up:
- [ ] Uptime monitoring (e.g., UptimeRobot, Pingdom)
- [ ] Error tracking (e.g., Sentry)
- [ ] Failed login attempt alerts
- [ ] Unusual traffic pattern detection

### 5. User Management
When adding more users:
- Create separate user accounts (don't share admin)
- Use role-based access control
- Implement password expiration (90 days recommended)
- Enable audit logging for admin actions

---

## 📋 Security Maintenance Schedule

### Daily
- Monitor API logs for errors
- Check Docker container health

### Weekly
- Review access logs
- Check for failed login attempts
- Verify backups are running

### Monthly
- Update Docker images
- Review and rotate API keys
- Audit user accounts and permissions
- Test backup restoration

### Quarterly
- Security audit
- Update dependencies (composer, npm)
- Review and update security policies
- Rotate JWT secret (requires user re-login)

---

## 🚨 Security Incident Response

If you suspect a security breach:

1. **Immediately:**
   - Change admin password
   - Rotate JWT secret in `.env`
   - Restart API container: `docker-compose restart api`

2. **Investigate:**
   - Check API logs: `docker-compose logs api | grep ERROR`
   - Review database for suspicious activity
   - Check for unauthorized user accounts

3. **Remediate:**
   - Block suspicious IP addresses
   - Force logout all users (rotate JWT secret)
   - Review and fix vulnerability
   - Update all dependencies

4. **Document:**
   - What happened
   - When it was discovered
   - Actions taken
   - Lessons learned

---

## 📞 Security Resources

**Password Generator:**
- https://bitwarden.com/password-generator/

**Security Testing:**
- OWASP ZAP: https://www.zaproxy.org/
- SSL Labs: https://www.ssllabs.com/ssltest/

**Security News:**
- CVE Database: https://cve.mitre.org/
- GitHub Security Advisories

---

## ✅ Current Security Status

**Overall Rating:** 🟢 GOOD (needs admin password change)

**Strengths:**
- Strong encryption (JWT, HTTPS)
- Proper authentication/authorization
- Docker isolation
- Rate limiting enabled
- Input sanitization

**Weaknesses:**
- ⚠️ Default admin password still active
- ⚠️ No automated backups configured
- ⚠️ No monitoring/alerting set up

**Next Priority Actions:**
1. Change admin password (highest priority)
2. Set up automated backups
3. Configure uptime monitoring
4. Add custom domain to Vercel

---

**Last Updated:** 2025-10-09
**Review Date:** 2026-01-09 (quarterly review)
