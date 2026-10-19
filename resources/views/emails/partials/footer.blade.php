          </td>
        </tr>

        <!-- Footer row -->
        <tr>
          <td style="padding:20px 24px;background:#f4f5f7;border-top:1px solid #e5e7eb;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" style="color:#6b7280;font-size:13px;line-height:1.5;">
                  @if ($logoUrl)
                    <div style="margin-bottom:8px;">
                      <img src="{{ $logoUrl }}" alt="{{ $brand }}" width="60" style="display:block;width:60px;height:auto;">
                    </div>
                  @endif
                  <div style="color:#111827;font-weight:bold;font-size:15px;margin-bottom:10px;">{{ $brand }}</div>
                  <div style="margin-bottom:10px;">
                    <a href="{{ route('shop') }}" style="color:#1A2E22;text-decoration:none;margin:0 8px;">Shop</a>
                    <a href="{{ route('contact') }}" style="color:#1A2E22;text-decoration:none;margin:0 8px;">Contact Us</a>
                    <a href="{{ route('privacy') }}" style="color:#1A2E22;text-decoration:none;margin:0 8px;">Privacy</a>
                    <a href="{{ route('terms') }}" style="color:#1A2E22;text-decoration:none;margin:0 8px;">Terms</a>
                    <a href="mailto:{{ $company->email1 ?: '' }}" style="color:#1A2E22;text-decoration:none;margin:0 8px;">Unsubscribe</a>
                  </div>
                  <div style="margin-top:10px;font-size:11px;color:#9ca3af;">
                    © {{ now()->year }} {{ $brand }}. All rights reserved.
                  </div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
      <!-- /Card -->
    </td>
  </tr>
</table>
<!-- /Outer wrapper -->
