package com.philmoresms.app.network

import retrofit2.Call
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query
import androidx.annotation.Keep

@Keep
interface PhilmoreApiService {

    // --- Auth ---
    @FormUrlEncoded
    @POST("auth.php?action=login")
    fun login(
        @Field("login") login: String,
        @Field("password") password: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("auth.php?action=register")
    fun register(
        @Field("username") username: String,
        @Field("email") email: String,
        @Field("password") password: String,
        @Field("phone") phone: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("auth.php?action=forgot_password")
    fun forgotPassword(
        @Field("email") email: String
    ): Call<BaseResponse>

    // --- Dashboard ---
    @POST("dashboard.php")
    fun getSummary(): Call<BaseResponse>

    // --- Profile ---
    @GET("user.php?action=profile")
    fun getProfile(): Call<UserResponse>

    @FormUrlEncoded
    @POST("user.php?action=update")
    fun updateProfile(
        @Field("username") username: String,
        @Field("email") email: String,
        @Field("phone") phone: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("user.php?action=change_password")
    fun changePassword(
        @Field("current_password") currentPassword: String,
        @Field("new_password") newPassword: String
    ): Call<BaseResponse>

    // --- Payments / Top-up ---
    @GET("payment.php?action=settings")
    fun getPaymentSettings(): Call<PaymentSettingsResponse>

    @FormUrlEncoded
    @POST("payment.php?action=submit_manual")
    fun submitManualPayment(
        @Field("amount") amount: String,
        @Field("reference") reference: String,
        @Field("date") date: String
    ): Call<BaseResponse>

    // --- Messaging ---
    @FormUrlEncoded
    @POST("messaging.php?action=send_sms")
    fun sendSms(
        @Field("senderID") senderId: String,
        @Field("recipients") recipients: String,
        @Field("message") message: String,
        @Field("route") route: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("messaging.php?action=send_voice")
    fun sendVoice(
        @Field("callerID") callerId: String,
        @Field("recipients") recipients: String,
        @Field("message") message: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("messaging.php?action=send_otp")
    fun sendOtp(
        @Field("senderID") senderId: String,
        @Field("recipients") recipients: String,
        @Field("otp") otp: String,
        @Field("template_code") templateCode: String
    ): Call<BaseResponse>

    // --- SMS History / Reports ---
    @GET("reports.php?action=messages")
    fun getMessages(): Call<MessagesResponse>

    // --- Sender IDs ---
    @GET("sender-ids.php?action=list")
    fun getSenderIds(): Call<SenderIdsResponse>

    @GET("sender-ids.php?action=corporate_list")
    fun getCorporateSenderIds(): Call<CorporateSenderIdsResponse>

    @FormUrlEncoded
    @POST("sender-ids.php?action=request")
    fun requestSenderId(
        @Field("senderID") senderId: String,
        @Field("message") message: String,
        @Field("type") type: String
    ): Call<BaseResponse>

    // --- SMS config (char/unit rules, mirroring the website) ---
    @GET("services.php?action=sms_config")
    fun getSmsConfig(): Call<SmsConfigResponse>

    // --- OTP templates ---
    @GET("services.php?action=otp_templates")
    fun getOtpTemplates(): Call<OtpTemplatesResponse>

    // --- Support ---
    @GET("support.php?action=list")
    fun getTickets(): Call<TicketsResponse>

    @GET("support.php?action=view")
    fun getTicket(@Query("id") id: String): Call<TicketDetailResponse>

    @FormUrlEncoded
    @POST("support.php?action=create")
    fun createTicket(
        @Field("subject") subject: String,
        @Field("message") message: String
    ): Call<BaseResponse>

    @FormUrlEncoded
    @POST("support.php?action=reply")
    fun replyTicket(
        @Field("id") id: String,
        @Field("message") message: String
    ): Call<BaseResponse>
}
