package com.philmoresms.app.network

import com.google.gson.annotations.SerializedName
import androidx.annotation.Keep

// --- Entity models ---

@Keep
data class User(
    @SerializedName("id") val id: String? = null,
    @SerializedName("username") val username: String? = null,
    @SerializedName("email") val email: String? = null,
    @SerializedName("phone") val phone: String? = null,
    @SerializedName("balance") val balance: Double? = null,
    @SerializedName("referral_code") val referralCode: String? = null,
    @SerializedName("created_at") val createdAt: String? = null
)

@Keep
data class SmsRecord(
    @SerializedName("id") val id: String? = null,
    @SerializedName("sender_id") val senderId: String? = null,
    @SerializedName("recipients") val recipients: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("cost") val cost: Double? = null,
    @SerializedName("status") val status: String? = null,
    @SerializedName("type") val type: String? = null,
    @SerializedName("created_at") val createdAt: String? = null
)

@Keep
data class SenderId(
    @SerializedName("id") val id: String? = null,
    @SerializedName("sender_id") val senderId: String? = null,
    @SerializedName("status") val status: String? = null,
    @SerializedName("created_at") val createdAt: String? = null
)

@Keep
data class SupportTicket(
    @SerializedName("ticket_id") val ticketId: String? = null,
    @SerializedName("subject") val subject: String? = null,
    @SerializedName("status") val status: String? = null,
    @SerializedName("updated_at") val updatedAt: String? = null
)

@Keep
data class TicketMessage(
    @SerializedName("message") val message: String? = null,
    @SerializedName("is_admin_reply") val isAdminReply: Int? = null,
    @SerializedName("created_at") val createdAt: String? = null
)

@Keep
data class ManualPayment(
    @SerializedName("enabled") val enabled: Boolean? = null,
    @SerializedName("bank_name") val bankName: String? = null,
    @SerializedName("account_name") val accountName: String? = null,
    @SerializedName("account_number") val accountNumber: String? = null,
    @SerializedName("instructions") val instructions: String? = null
)

@Keep
data class OtpTemplate(
    @SerializedName("template_code") val templateCode: String? = null,
    @SerializedName("template_name") val templateName: String? = null,
    @SerializedName("message_body") val messageBody: String? = null,
    @SerializedName("status") val status: String? = null
)

// --- Response wrappers (each mirrors the backend JSON envelope) ---

@Keep
data class UserResponse(
    @SerializedName("status") val status: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("user") val user: User? = null
)

@Keep
data class MessagesResponse(
    @SerializedName("status") val status: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("messages") val messages: List<SmsRecord>? = null
)

@Keep
data class SenderIdsResponse(
    @SerializedName("status") val status: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("sender_ids") val senderIds: List<SenderId>? = null
)

@Keep
data class TicketsResponse(
    @SerializedName("status") val status: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("tickets") val tickets: List<SupportTicket>? = null
)

@Keep
data class TicketDetailResponse(
    @SerializedName("status") val status: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("ticket") val ticket: SupportTicket? = null,
    @SerializedName("messages") val messages: List<TicketMessage>? = null
)

@Keep
data class PaymentSettingsResponse(
    @SerializedName("status") val status: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("manual_payment") val manualPayment: ManualPayment? = null,
    @SerializedName("vat_percentage") val vatPercentage: Double? = null,
    @SerializedName("currency") val currency: String? = null,
    @SerializedName("currency_symbol") val currencySymbol: String? = null
)

@Keep
data class OtpTemplatesResponse(
    @SerializedName("status") val status: String? = null,
    @SerializedName("message") val message: String? = null,
    @SerializedName("templates") val templates: List<OtpTemplate>? = null
)
