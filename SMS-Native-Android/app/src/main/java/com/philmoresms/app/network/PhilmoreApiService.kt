package com.philmoresms.app.network

import retrofit2.Call
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.POST
import androidx.annotation.Keep

@Keep
interface PhilmoreApiService {

    @FormUrlEncoded
    @POST("auth.php?action=login")
    fun login(
        @Field("login") login: String,
        @Field("password") password: String
    ): Call<BaseResponse>

    @POST("dashboard.php")
    fun getSummary(): Call<BaseResponse>

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
}
