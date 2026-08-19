package com.philmoresms.app.ui.screens.login

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.RetrofitClient
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class LoginViewModel : ViewModel() {
    var login by mutableStateOf("")
    var password by mutableStateOf("")
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var loginSuccess by mutableStateOf(false)

    fun onLoginClick() {
        if (login.isBlank() || password.isBlank()) {
            error = "Please fill all fields"
            return
        }

        loading = true
        error = null

        viewModelScope.launch {
            try {
                val body = RetrofitClient.apiService.login(login, password).await()
                if (body.status == "success") {
                    body.token?.let {
                        RetrofitClient.setToken(it)
                    }
                    loginSuccess = true
                } else {
                    error = body.message ?: "Login failed"
                }
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Login failed (HTTP ${e.code()})"
            } catch (e: Exception) {
                val msg = e.message ?: ""
                error = if (msg.contains("JsonReader") || msg.contains("malformed") || msg.contains("ParameterizedType")) {
                    "Server Error: The server returned an invalid response. Please check your Database configuration in app/config.php."
                } else {
                    msg.ifBlank { "Network Error: Please check your internet connection" }
                }
            } finally {
                loading = false
            }
        }
    }
}
