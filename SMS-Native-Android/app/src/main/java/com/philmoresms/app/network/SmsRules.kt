package com.philmoresms.app.network

import kotlin.math.ceil

/**
 * Mirrors the website's SMS rules (app/helpers.php + send-sms.php):
 *  - SMS parts: 1 unit if length <= chars_1unit (default 160), else ceil(length / chars_multunit) (default 153).
 *  - Recipient count: split on comma / space / semicolon / newline, count non-empty entries.
 */
object SmsRules {

    fun smsParts(message: String, chars1Unit: Int = 160, charsMultUnit: Int = 153): Int {
        val len = message.length
        val first = chars1Unit.coerceAtLeast(1)
        val rest = charsMultUnit.coerceAtLeast(1)
        return if (len <= first) 1 else ceil(len.toDouble() / rest).toInt()
    }

    fun recipientCount(text: String): Int {
        if (text.isBlank()) return 0
        return text.split(Regex("[\\s,;\\n]+")).count { it.isNotEmpty() }
    }
}
